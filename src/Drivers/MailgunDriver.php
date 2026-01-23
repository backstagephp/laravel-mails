<?php

namespace Backstage\Mails\Drivers;

use Backstage\Mails\Contracts\MailDriverContract;
use Backstage\Mails\Enums\EventType;
use Backstage\Mails\Enums\Provider;
use Illuminate\Http\Client\Response;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class MailgunDriver extends MailDriver implements MailDriverContract
{
    public function registerWebhooks($components): void
    {
        $trackingConfig = (array) config('mails.logging.tracking');

        $apiKey = config('services.mailgun.secret');
        $domain = config('services.mailgun.domain');
        $scheme = config('services.mailgun.scheme', 'https');
        $endpoint = config('services.mailgun.endpoint', 'api.mailgun.net');

        $webhookUrl = URL::signedRoute('mails.webhook', ['provider' => Provider::MAILGUN]);

        $events = [];

        if ((bool) $trackingConfig['opens']) {
            $events[] = 'opened';
        }

        if ((bool) $trackingConfig['clicks']) {
            $events[] = 'clicked';
        }

        if ((bool) $trackingConfig['deliveries']) {
            $events[] = 'accepted';
            $events[] = 'delivered';
        }

        if ((bool) $trackingConfig['bounces']) {
            $events[] = 'permanent_fail';
            $events[] = 'temporary_fail';
        }

        if ((bool) $trackingConfig['complaints']) {
            $events[] = 'complained';
        }

        if ((bool) $trackingConfig['unsubscribes']) {
            $events[] = 'unsubscribed';
        }

        foreach ($events as $event) {
            $response = Http::withBasicAuth('api', $apiKey)
                ->asMultipart()
                ->post("$scheme://$endpoint/v3/domains/$domain/webhooks", [
                    'id' => $event,
                    'url' => $webhookUrl,
                ]);

            $message = $response->json()['message'] ?? null;

            if ($response->successful()) {
                $components->info("Created Mailgun webhook for: $event");
            } elseif ($message === 'Webhook already exists') {
                $components->warn("A Mailgun webhook already exists for: $event");
                $components->info("Please make sure that it is: $webhookUrl");
            } else {
                $components->warn("Failed to create Mailgun webhook for: $event");
                $components->error($message);
            }
        }
    }

    public function verifyWebhookSignature(array $payload): bool
    {
        if (app()->runningUnitTests()) {
            return true;
        }

        if (empty($payload['signature']['timestamp']) || empty($payload['signature']['token']) || empty($payload['signature']['signature'])) {
            return false;
        }

        $hmac = hash_hmac('sha256', $payload['signature']['timestamp'].$payload['signature']['token'], (string) config('services.mailgun.webhook_signing_key'));

        if (function_exists('hash_equals')) {
            return hash_equals($hmac, $payload['signature']['signature']);
        }

        return $hmac === $payload['signature']['signature'];
    }

    public function attachUuidToMail(MessageSending $messageSending, string $uuid): MessageSending
    {
        $messageSending->message->getHeaders()->addTextHeader('X-Mailgun-Variables', json_encode([config('mails.headers.uuid') => $uuid]));

        return $messageSending;
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

    public function unsuppressEmailAddress(string $address, ?int $stream_id = null): Response
    {
        $pendingRequest = Http::asJson()
            ->withBasicAuth('api', config('services.mailgun.secret'))
            ->baseUrl(config('services.mailgun.endpoint').'/v3/');

        return $pendingRequest->delete(config('services.mailgun.domain').'/unsubscribes/'.$address);
    }
}
