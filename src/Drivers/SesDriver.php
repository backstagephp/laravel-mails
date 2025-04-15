<?php

namespace Vormkracht10\Mails\Drivers;

use Aws\Ses\SesClient;
use Aws\Sns\SnsClient;
use Illuminate\Http\Client\Response;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Vormkracht10\Mails\Contracts\MailDriverContract;
use Vormkracht10\Mails\Enums\EventType;
use Vormkracht10\Mails\Enums\Provider;

class SesDriver extends MailDriver implements MailDriverContract
{
    public function registerWebhooks($components): void
    {
        /** @var SesTransport|null $sesTransport */
        $sesTransport = Mail::driver('ses');
        if ($sesTransport) {
            $components->warn("Failed to create Ses webhook");
            $components->error("There is no Amazon SES Driver configured in your laravel application.");
            return;
        }

        $trackingConfig = (array)config('mails.logging.tracking');

        // send - The call was successful and Amazon SES is attempting to deliver the email.
        // reject - Amazon SES determined that the email contained a virus and rejected it.
        // bounce - The recipient's mail server permanently rejected the email. This corresponds to a hard bounce.
        // complaint - The recipient marked the email as spam.
        // delivery - Amazon SES successfully delivered the email to the recipient's mail server.
        // open - The recipient received the email and opened it in their email client.
        // click - The recipient clicked one or more links in the email.
        // renderingFailure - Amazon SES did not send the email because of a template rendering issue.
        $events = [];
        $eventTypes = [];

        if ((bool)$trackingConfig['opens']) {
            $events[] = 'open';
            $eventTypes[] = 'Delivery';
        }

        if ((bool)$trackingConfig['clicks']) {
            $events[] = 'click';
            $eventTypes[] = 'Delivery';
        }

        if ((bool)$trackingConfig['deliveries']) {
            $events[] = 'delivery';
            $eventTypes[] = 'Delivery';
        }

        if ((bool)$trackingConfig['bounces']) {
            $events[] = 'reject';
            $events[] = 'bounce';
            $events[] = 'renderingFailure';
            $eventTypes[] = 'Bounce';
        }

        if ((bool)$trackingConfig['complaints']) {
            $events[] = 'complaint';
            $eventTypes[] = 'Complaint';
        }

        $sesClient = $sesTransport->ses();
        $configurationSet = config('services.ses.configuration_set_name', 'laravel-mails-ses-webhook');

        try {
            // 1. Create Configuration Set
            $sesClient->createConfigurationSet([
                'ConfigurationSet' => [
                    'Name' => $configurationSet,
                ],
            ]);


            // 2. Create a SNS Topic
            $config = config('services.sns', config('services.ses', []));
            $snsClient = $this->createSnsClient($config);
            $result = $snsClient->createTopic([
                'Name' => $configurationSet,
            ]);
            $topicArn = $result->get('TopicArn');

            // 3. Give access to SES to publish notifications to the topic.
            $snsClient->addPermission([
                'AWSAccountId' => $config['account_id'] ?? '',
                'ActionName' => 'Publish',
                'Label' => 'ses-notification-policy',
                'TopicArn' => $topicArn,
            ]);

            // 4. Set the channels
            $eventTypes = array_unique($eventTypes);
            foreach ($eventTypes as $eventType) {
                $sesClient->setIdentityNotificationTopic([
                    'Identity' => config('services.ses.identity', config('mail.from.address')),
                    'NotificationType' => $eventType,
                    'SnsTopic' => $topicArn,
                ]);
            }

            // 5. Register SNS as the event destination
            $sesClient->createConfigurationSetEventDestination([
                'ConfigurationSetName' => $configurationSet,
                'EventDestination' => [
                    'Enabled' => true,
                    'Name' => $configurationSet,
                    'MatchingEventTypes' => $events,
                    'SNSDestination' => [
                        'TopicARN' => $topicArn,
                    ]
                ]
            ]);

            // 5. Subscribe to the topic
            $webhookUrl = URL::signedRoute('mails.webhook', ['provider' => Provider::SES]);
            $scheme = config('services.ses.scheme', 'https');
            $snsClient->subscribe([
                'Endpoint' => $webhookUrl,
                'TopicArn' => $topicArn,
                'Protocol' => $scheme
            ]);

        } catch (\Throwable $e) {
            report($e);
            $components->warn("Failed to create Ses webhook");
            $components->error($e->getMessage());
            return;
        }

        $components->info("Created SES Webhooks for: " . implode(", ", $eventTypes));
    }

    public function verifyWebhookSignature(array $payload): bool
    {
        dd($payload);
        if (app()->runningUnitTests()) {
            return true;
        }

        if (empty($payload['signature']['timestamp']) || empty($payload['signature']['token']) || empty($payload['signature']['signature'])) {
            return false;
        }

        $hmac = hash_hmac('sha256', $payload['signature']['timestamp'] . $payload['signature']['token'], config('services.mailgun.webhook_signing_key'));

        if (function_exists('hash_equals')) {
            return hash_equals($hmac, $payload['signature']['signature']);
        }

        return $hmac === $payload['signature']['signature'];
    }

    public function attachUuidToMail(MessageSending $event, string $uuid): MessageSending
    {
        $event->message->getHeaders()->addTextHeader('X-Mailgun-Variables', json_encode([config('mails.headers.uuid') => $uuid]));

        return $event;
    }

    public function getUuidFromPayload(array $payload): ?string
    {
        return $payload['event-data']['user-variables'][$this->uuidHeaderName] ?? null;
    }

    protected function getTimestampFromPayload(array $payload): string
    {
        return $payload['event-data']['timestamp'];
    }

    public function eventMapping(): array
    {
        return [
            EventType::ACCEPTED->value => ['event-data.event' => 'accepted'],
            EventType::CLICKED->value => ['event-data.event' => 'clicked'],
            EventType::COMPLAINED->value => ['event-data.event' => 'complained'],
            EventType::DELIVERED->value => ['event-data.event' => 'delivered'],
            EventType::HARD_BOUNCED->value => ['event-data.event' => 'failed', 'event-data.severity' => 'permanent'],
            EventType::OPENED->value => ['event-data.event' => 'opened'],
            EventType::SOFT_BOUNCED->value => ['event-data.event' => 'failed', 'event-data.severity' => 'temporary'],
            EventType::UNSUBSCRIBED->value => ['event-data.event' => 'unsubscribed'],
        ];
    }

    public function dataMapping(): array
    {
        return [
            'ip_address' => 'event-data.ip',
            'platform' => 'event-data.client-info.device-type',
            'os' => 'event-data.client-info.client-os',
            'browser' => 'event-data.client-info.client-name',
            'user_agent' => 'event-data.client-info.user-agent',
            'city' => 'event-data.geolocation.city',
            'country_code' => 'event-data.geolocation.country',
            'link' => 'event-data.url',
            'tag' => 'event-data.tags',
        ];
    }

    public function unsuppressEmailAddress(string $address): Response
    {
        $client = Http::asJson()
            ->withBasicAuth('api', config('services.mailgun.secret'))
            ->baseUrl(config('services.mailgun.endpoint') . '/v3/');

        return $client->delete(config('services.mailgun.domain') . '/unsubscribes/' . $address);
    }

    protected function createSnsClient(array $config): SnsClient
    {
        $config = array_merge(
            [
                'version' => 'latest'
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
