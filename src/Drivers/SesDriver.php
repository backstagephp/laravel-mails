<?php

namespace Backstage\Mails\Laravel\Drivers;

use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use Aws\SesV2\SesV2Client;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Aws\Sns\SnsClient;
use Backstage\Mails\Laravel\Contracts\MailDriverContract;
use Backstage\Mails\Laravel\Enums\EventType;
use Backstage\Mails\Laravel\Enums\Provider;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class SesDriver extends MailDriver implements MailDriverContract
{
    public function registerWebhooks($components): void
    {
        if (! class_exists(SesClient::class)) {
            $components->warn('Failed to create SES webhook');
            $components->error('The AWS SDK is missing. Run: composer require aws/aws-sdk-php aws/aws-php-sns-message-validator');

            return;
        }

        $trackingConfig = (array) config('mails.logging.tracking');

        // Configuration Set Event Destination event types. Rendering failures
        // are not subscribed to: they only occur for SES template sending,
        // which Laravel's mailer never uses (it renders mails itself).
        $events = [];

        if (! empty($trackingConfig['opens'])) {
            $events[] = 'open';
        }

        if (! empty($trackingConfig['clicks'])) {
            $events[] = 'click';
        }

        if (! empty($trackingConfig['deliveries'])) {
            $events[] = 'delivery';
        }

        if (! empty($trackingConfig['bounces'])) {
            $events[] = 'reject';
            $events[] = 'bounce';
        }

        if (! empty($trackingConfig['complaints'])) {
            $events[] = 'complaint';
        }

        $config = (array) config('services.ses', []);
        $configurationSet = config('services.ses.configuration_set_name', 'laravel-mails-ses-webhook');

        try {
            $sesClient = $this->createSesClient($config);

            // 1. Get or create the Configuration Set
            try {
                $sesClient->createConfigurationSet([
                    'ConfigurationSet' => [
                        'Name' => $configurationSet,
                    ],
                ]);
            } catch (AwsException $e) {
                if ($e->getAwsErrorCode() !== 'ConfigurationSetAlreadyExists') {
                    throw $e;
                }
            }

            // 2. Create a SNS Topic (idempotent - returns existing topic ARN if it already exists)
            $snsConfig = (array) config('services.sns', $config);
            $snsClient = $this->createSnsClient($snsConfig);
            $result = $snsClient->createTopic([
                'Name' => $configurationSet,
            ]);
            $topicArn = $result->get('TopicArn');

            // 3. Give access to SES to publish notifications to the topic
            try {
                $snsClient->addPermission([
                    'AWSAccountId' => [$snsConfig['account_id'] ?? ''],
                    'ActionName' => ['Publish'],
                    'Label' => 'ses-notification-policy',
                    'TopicArn' => $topicArn,
                ]);
            } catch (AwsException $e) {
                if ($e->getAwsErrorCode() !== 'InvalidParameter'
                    || ! str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }

            // 4. Register SNS as the event destination (remove existing first to avoid duplicates)
            $eventDestinationName = $configurationSet.'-sns';

            try {
                $sesClient->deleteConfigurationSetEventDestination([
                    'ConfigurationSetName' => $configurationSet,
                    'EventDestinationName' => $eventDestinationName,
                ]);
            } catch (AwsException $e) {
                if ($e->getAwsErrorCode() !== 'EventDestinationDoesNotExist') {
                    throw $e;
                }
            }

            $sesClient->createConfigurationSetEventDestination([
                'ConfigurationSetName' => $configurationSet,
                'EventDestination' => [
                    'Enabled' => true,
                    'Name' => $eventDestinationName,
                    'MatchingEventTypes' => $events,
                    'SNSDestination' => [
                        'TopicARN' => $topicArn,
                    ],
                ],
            ]);

            // 5. Subscribe to the topic
            $webhookUrl = URL::signedRoute('mails.webhook', ['provider' => Provider::SES]);
            $scheme = config('services.ses.scheme', 'https');
            $snsClient->subscribe([
                'Endpoint' => $webhookUrl,
                'TopicArn' => $topicArn,
                'Protocol' => $scheme,
            ]);
        } catch (\Throwable $e) {
            report($e);

            $components->warn('Failed to create SES webhook');
            $components->error($e->getMessage());

            return;
        }

        $components->info('Created SES Webhooks for: '.implode(', ', $events));
    }

    public function verifyWebhookSignature(array $payload): bool
    {
        if (app()->runningUnitTests()) {
            return true;
        }

        $validator = new MessageValidator(function ($url) {
            return Http::timeout(10)->get($url)->body();
        });

        try {
            // Built from the stored payload rather than php://input, because
            // webhooks may be verified on a queue worker with no live request.
            $message = new Message($payload);
            $validator->validate($message);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        if ($message['Type'] === 'SubscriptionConfirmation') {
            Http::timeout(10)->get($message['SubscribeURL'])->throw();
        }

        return true;
    }

    public function attachUuidToMail(MessageSending $event, string $uuid): MessageSending
    {
        $headers = $event->message->getHeaders();

        $headers->addTextHeader($this->uuidHeaderName, $uuid);

        if ($this->shouldAttachConfigurationSet($event)) {
            $headers->addTextHeader(
                'X-SES-CONFIGURATION-SET',
                config('services.ses.configuration_set_name', 'laravel-mails-ses-webhook'),
            );
        }

        return $event;
    }

    /**
     * SES only publishes events for mails sent under the configuration set the
     * webhook is registered on, so attach it per message unless the mailer is
     * already configured with one.
     */
    protected function shouldAttachConfigurationSet(MessageSending $event): bool
    {
        $mailer = $event->data['mailer'] ?? 'ses';

        return ! config("mail.mailers.{$mailer}.options.ConfigurationSetName");
    }

    /**
     * SES delivers its events wrapped in an SNS envelope, where the actual event
     * is a JSON string in the Message key. Everything downstream expects the
     * unwrapped event, so payloads are normalized here before being read.
     */
    protected function parseSnsMessage(array $payload): array
    {
        if (isset($payload['Message']) && is_string($payload['Message'])) {
            return json_decode($payload['Message'], true) ?? [];
        }

        return $payload;
    }

    public function getUuidFromPayload(array $payload): ?string
    {
        $sesMessage = $this->parseSnsMessage($payload);

        $headers = $sesMessage['mail']['headers'] ?? [];

        $header = Arr::first($headers, function ($header) {
            return $header['name'] === config('mails.headers.uuid');
        });

        return $header['value'] ?? null;
    }

    protected function getTimestampFromPayload(array $payload): string
    {
        $sesMessage = $this->parseSnsMessage($payload);

        foreach (['click', 'open', 'bounce', 'complaint', 'delivery', 'mail'] as $event) {
            if (isset($sesMessage[$event]['timestamp'])) {
                return $sesMessage[$event]['timestamp'];
            }
        }

        return $payload['Timestamp'] ?? now()->toIso8601String();
    }

    public function getDataFromPayload(array $payload): array
    {
        $sesMessage = $this->parseSnsMessage($payload);

        $data = [];

        foreach ($this->dataMapping() as $key => $paths) {
            foreach ($paths as $path) {
                $value = data_get($sesMessage, $path);

                if ($value !== null) {
                    $data[$key] = is_array($value) ? json_encode($value) : $value;

                    break;
                }
            }
        }

        return array_merge($data, [
            'payload' => $payload,
            'type' => $this->getEventFromPayload($sesMessage),
            'occurred_at' => $this->getTimestampFromPayload($sesMessage),
        ]);
    }

    public function getEventFromPayload(array $payload): string
    {
        $sesMessage = $this->parseSnsMessage($payload);

        // A Reject means SES refused to send the mail (it detected a virus),
        // so it will never arrive: a hard bounce.
        if (($sesMessage['eventType'] ?? null) === 'Reject') {
            return EventType::HARD_BOUNCED->value;
        }

        // SES could not tell whether an Undetermined bounce is permanent, and
        // the address may still be deliverable, so treat it as soft.
        if (($sesMessage['eventType'] ?? null) === 'Bounce'
            && ($sesMessage['bounce']['bounceType'] ?? null) === 'Undetermined') {
            return EventType::SOFT_BOUNCED->value;
        }

        return parent::getEventFromPayload($sesMessage);
    }

    public function eventMapping(): array
    {
        return [
            EventType::ACCEPTED->value => ['eventType' => 'Send'],
            EventType::CLICKED->value => ['eventType' => 'Click'],
            EventType::COMPLAINED->value => ['eventType' => 'Complaint'],
            EventType::DELIVERED->value => ['eventType' => 'Delivery'],
            EventType::OPENED->value => ['eventType' => 'Open'],
            EventType::HARD_BOUNCED->value => ['eventType' => 'Bounce', 'bounce.bounceType' => 'Permanent'],
            EventType::SOFT_BOUNCED->value => ['eventType' => 'Bounce', 'bounce.bounceType' => 'Transient'],
        ];
    }

    /**
     * Unlike the other drivers a value is a list of candidate paths, because SES
     * reports the same attribute under a different key per event type.
     */
    public function dataMapping(): array
    {
        return [
            'ip_address' => ['click.ipAddress', 'open.ipAddress'],
            'browser' => ['mail.client-info.client-name'],
            'user_agent' => ['click.userAgent', 'open.userAgent', 'complaint.userAgent'],
            'link' => ['click.link'],
            'tag' => ['click.linkTags'],
        ];
    }

    protected function createSesClient(array $config): SesClient
    {
        return new SesClient($this->addCredentials($config));
    }

    protected function createSesV2Client(array $config): SesV2Client
    {
        return new SesV2Client($this->addCredentials($config));
    }

    protected function createSnsClient(array $config): SnsClient
    {
        return new SnsClient($this->addCredentials($config));
    }

    protected function addCredentials(array $config): array
    {
        $config = array_merge(['version' => 'latest'], $config);

        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);

            if (! empty($config['token'])) {
                $config['credentials']['token'] = $config['token'];
            }
        }

        return Arr::except($config, ['token']);
    }

    public function unsuppressEmailAddress(string $address, ?int $stream_id = null): Response
    {
        try {
            // The account-level suppression list only exists in the SESv2 API,
            // so this cannot go through the mailer's (v1) client.
            $this->createSesV2Client((array) config('services.ses', []))->deleteSuppressedDestination([
                'EmailAddress' => $address,
            ]);

            return new Response(new Psr7Response(200));
        } catch (\Throwable $e) {
            report($e);

            return new Response(new Psr7Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => $e->getMessage()])));
        }
    }
}
