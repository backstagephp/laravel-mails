<?php

namespace Backstage\Mails\Drivers;

use Aws\Exception\AwsException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Aws\Sns\SnsClient;
use Backstage\Mails\Contracts\MailDriverContract;
use Backstage\Mails\Enums\EventType;
use Backstage\Mails\Enums\Provider;
use Illuminate\Http\Client\Response;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SesDriver extends MailDriver implements MailDriverContract
{
    public function registerWebhooks($components): void
    {
        $mailer = Mail::driver('ses');
        if ($mailer === null) {
            $components->warn('Failed to create SES webhook');
            $components->error('There is no Amazon SES Driver configured in your Laravel application.');

            return;
        }

        $trackingConfig = (array) config('mails.logging.tracking');

        // Configuration Set Event Destination event types (for open/click/delivery/bounce/complaint tracking)
        $events = [];

        // SNS Identity Notification types (only Bounce, Complaint, Delivery are valid)
        $eventTypes = [];

        if ((bool) $trackingConfig['opens']) {
            $events[] = 'open';
        }

        if ((bool) $trackingConfig['clicks']) {
            $events[] = 'click';
        }

        if ((bool) $trackingConfig['deliveries']) {
            $events[] = 'delivery';
            $eventTypes[] = 'Delivery';
        }

        if ((bool) $trackingConfig['bounces']) {
            $events[] = 'reject';
            $events[] = 'bounce';
            $events[] = 'renderingFailure';
            $eventTypes[] = 'Bounce';
        }

        if ((bool) $trackingConfig['complaints']) {
            $events[] = 'complaint';
            $eventTypes[] = 'Complaint';
        }

        /** @var SesTransport $sesTransport */
        $sesTransport = $mailer->getSymfonyTransport();
        $sesClient = $sesTransport->ses();
        $configurationSet = config('services.ses.configuration_set_name', 'laravel-mails-ses-webhook');

        try {
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
            $config = config('services.sns', config('services.ses', []));
            $snsClient = $this->createSnsClient($config);
            $result = $snsClient->createTopic([
                'Name' => $configurationSet,
            ]);
            $topicArn = $result->get('TopicArn');

            // 3. Give access to SES to publish notifications to the topic
            try {
                $snsClient->addPermission([
                    'AWSAccountId' => [$config['account_id'] ?? ''],
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

            // 4. Set identity notification topics for Bounce/Complaint/Delivery
            $eventTypes = array_unique($eventTypes);
            foreach ($eventTypes as $eventType) {
                $identity = config('services.ses.identity', config('mail.from.address'));

                $sesClient->setIdentityNotificationTopic([
                    'Identity' => $identity,
                    'NotificationType' => $eventType,
                    'SnsTopic' => $topicArn,
                ]);

                $sesClient->setIdentityHeadersInNotificationsEnabled([
                    'Identity' => $identity,
                    'NotificationType' => $eventType,
                    'Enabled' => true,
                ]);
            }

            // 5. Register SNS as the event destination (remove existing first to avoid duplicates)
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

            // 6. Subscribe to the topic
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

        $message = Message::fromRawPostData();

        $validator = new MessageValidator(function ($url) {
            return Http::timeout(10)->get($url)->body();
        });

        try {
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
        $event->message->getHeaders()->addTextHeader($this->uuidHeaderName, $uuid);

        return $event;
    }

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
        foreach (['click', 'open', 'bounce', 'complaint', 'delivery', 'mail'] as $event) {
            if (isset($payload[$event]['timestamp'])) {
                return $payload[$event]['timestamp'];
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

    public function eventMapping(): array
    {
        return [
            EventType::ACCEPTED->value => ['eventType' => 'Send'],
            EventType::CLICKED->value => ['eventType' => 'Click'],
            EventType::COMPLAINED->value => ['eventType' => 'Complaint'],
            EventType::DELIVERED->value => ['eventType' => 'Delivery'],
            EventType::OPENED->value => ['eventType' => 'Open'],
            EventType::HARD_BOUNCED->value => ['eventType' => 'Bounce', 'bounce.bounceType' => 'Permanent'],
            EventType::SOFT_BOUNCED->value => ['eventType' => 'Bounce', 'bounce.bounceType' => 'Temporary'],
        ];
    }

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

    public function unsuppressEmailAddress(string $address, ?int $stream_id = null): Response
    {
        // SES doesn't support unsuppressing email addresses via this package's API
        return new Response(new \GuzzleHttp\Psr7\Response(200, [], 'Not supported'));
    }

    protected function createSnsClient(array $config): SnsClient
    {
        $config = array_merge(
            [
                'version' => 'latest',
            ],
            $config
        );

        return new SnsClient($this->addSnsCredentials($config));
    }

    protected function addSnsCredentials(array $config): array
    {
        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);

            if (! empty($config['token'])) {
                $config['credentials']['token'] = $config['token'];
            }
        }

        return Arr::except($config, ['token']);
    }
}
