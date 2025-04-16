<?php

namespace Vormkracht10\Mails\Drivers;

use Aws\Exception\AwsException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
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
        $mailer = Mail::driver('ses');
        if ($mailer === null) {
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

        /** @var SesTransport $sesTransport */
        $sesTransport = $mailer->getSymfonyTransport();
        $sesClient = $sesTransport->ses();
        $configurationSet = config('services.ses.options.ConfigurationSetName', 'laravel-mails-ses-webhook');

        try {
            // 1. Get or create the Configuration Set
            try {
                $sesClient->createConfigurationSet([
                    'ConfigurationSet' => [
                        'Name' => $configurationSet,
                    ],
                ]);
            } catch (AwsException $e) {
                // Already exists, move on!
                if ($e->getAwsErrorCode() !== 'ConfigurationSetAlreadyExists') {
                    throw $e;
                }
            }

            // 2. Create a SNS Topic
            $config = config('services.sns', config('services.ses', []));
            $snsClient = $this->createSnsClient($config);
            $result = $snsClient->createTopic([
                'Name' => $configurationSet,
            ]);
            $topicArn = $result->get('TopicArn');

            // 3. Give access to SES to publish notifications to the topic.
            $snsClient->addPermission([
                'AWSAccountId' => [$config['account_id'] ?? ''],
                'ActionName' => ['Publish'],
                'Label' => 'ses-notification-policy',
                'TopicArn' => $topicArn,
            ]);

            // 4. Set the channels
            $eventTypes = array_unique($eventTypes);
            foreach ($eventTypes as $eventType) {
                $identity = config('services.ses.identity', config('mail.from.address'));
                // get notified for the various types of events via SNS
                $sesClient->setIdentityNotificationTopic([
                    'Identity' => $identity,
                    'NotificationType' => $eventType,
                    'SnsTopic' => $topicArn,
                ]);

                // Force SNS to include the SES mail headers in the notification
                $sesClient->setIdentityHeadersInNotificationsEnabled([
                    'Identity' => $identity,
                    'NotificationType' => $eventType,
                    'Enabled' => true,
                ]);
            }

            // 5. Register SNS as the event destination
            $sesClient->createConfigurationSetEventDestination([
                'ConfigurationSetName' => $configurationSet,
                'EventDestination' => [
                    'Enabled' => true,
                    'Name' => $configurationSet . '-' . uniqid(),
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
        if (app()->runningUnitTests()) {
            return true;
        }

        // Weird SNS thing, you need to read the raw post body
        $message = Message::fromRawPostData();

        $validator = (new MessageValidator(function ($url) {
            return Http::timeout(10)->get($url)->body();
        }));

        try {
            $validator->validate($message);
            if ($message['Type'] === 'SubscriptionConfirmation') {
                Http::timeout(10)->get($message['SubscribeURL'])->throw();
            }
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function attachUuidToMail(MessageSending $event, string $uuid): MessageSending
    {
        $event->message->getHeaders()->addTextHeader($this->uuidHeaderName, $uuid);

        return $event;
    }

    public function getUuidFromPayload(array $payload): ?string
    {
        $message = Message::fromRawPostData();
        $headers = $message['Message']['mail']['headers'] ?? [];
        $header = Arr::first($headers, function ($header) {
            return $header['name'] === config('mails.headers.uuid');
        });

        return $header['value'] ?? null;
    }

    protected function getTimestampFromPayload(array $payload): string
    {
        foreach (['click', 'open', 'bounce', 'complaint', 'delivery', 'mail'] as $event) {
            if (isset($payload['Message'][$event]['timestamp'])) {
                return $payload['Message'][$event]['timestamp'];
            }
        }
        return $payload['Message']['Timestamp'];
    }

    public function getDataFromPayload(array $payload): array
    {
        $message = Message::fromRawPostData();
        $payload = $message->toArray();

        return collect($this->dataMapping())
            ->mapWithKeys(function ($values, $key) use ($payload) {
                foreach ($values as $value) {
                    $value = data_get($payload, $value);
                    if ($value !== null) {
                        return [$key => $value];
                    }
                }
                return null;
            })
            ->filter()
            ->merge([
                'payload' => $payload,
                'type' => $this->getEventFromPayload($payload),
                'occurred_at' => $this->getTimestampFromPayload($payload),
            ])
            ->toArray();
    }

    public function eventMapping(): array
    {
        return [
            EventType::ACCEPTED->value => ['Message.eventType' => 'Send'],
            EventType::CLICKED->value => ['Message.eventType' => 'Click'],
            EventType::COMPLAINED->value => ['Message.eventType' => 'Complaint'],
            EventType::DELIVERED->value => ['Message.eventType' => 'Delivery'],
            EventType::OPENED->value => ['Message.eventType' => 'Open'],
            EventType::HARD_BOUNCED->value => ['Message.eventType' => 'Bounce', 'Message.bounce.bounceType' => 'Permanent'],
            EventType::SOFT_BOUNCED->value => ['Message.eventType' => 'Bounce', 'Message.bounce.bounceType' => 'Temporary'],
        ];
    }

    public function dataMapping(): array
    {
        return [
            'ip_address' => ['Message.click.ipAddress', 'Message.open.ipAddress'],
            'browser' => ['Message.mail.client-info.client-name'],
            'user_agent' => ['Message.click.userAgent', 'Message.open.userAgent','Message.complaint.userAgent'],
            'link' => ['Message.click.link'],
            'tag' => ['Message.click.linkTags'],
        ];
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
        if (!empty($config['key']) && !empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);

            if (!empty($config['token'])) {
                $config['credentials']['token'] = $config['token'];
            }
        }

        return Arr::except($config, ['token']);
    }
}
