<?php

namespace Backstage\Mails\Laravel\Drivers;

use Backstage\Mails\Laravel\Contracts\MailDriverContract;
use Backstage\Mails\Laravel\Enums\EventType;
use Backstage\Mails\Laravel\Enums\Provider;
use Illuminate\Http\Client\Response;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ResendDriver extends MailDriver implements MailDriverContract
{
    public function registerWebhooks($components): void
    {
        $webhookUrl = URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND]);

        $apiKey = (string) config('services.resend.key');

        if ($apiKey === '') {
            $components->warn('No Resend API key found in services.resend.key.');
            $components->info('Add the key and run this command again, or add this endpoint by hand in the Resend dashboard:');
            $components->bulletList([$webhookUrl]);

            return;
        }

        $events = $this->webhookEvents();

        if ($events === []) {
            $components->warn('No Resend webhook was created: every event in mails.logging.tracking is disabled.');

            return;
        }

        $existing = Http::withToken($apiKey)->get('https://api.resend.com/webhooks');

        if (! $existing->successful()) {
            $components->warn('Failed to list the existing Resend webhooks.');
            $components->error($this->errorMessage($existing));

            return;
        }

        // The signature is derived from the app key, so a rotated key would make
        // an already registered webhook look new and have Resend deliver every
        // event twice. The endpoint itself is what identifies our webhook.
        $endpoints = collect($existing->json('data') ?? [])
            ->map(fn (array $webhook): string => Str::before((string) ($webhook['endpoint'] ?? ''), '?'));

        if ($endpoints->contains(Str::before($webhookUrl, '?'))) {
            $components->info('A Resend webhook already exists for this application');

            return;
        }

        $response = Http::withToken($apiKey)->post('https://api.resend.com/webhooks', [
            'endpoint' => $webhookUrl,
            'events' => $events,
        ]);

        if ($response->successful()) {
            $components->info('Created Resend webhook for: '.implode(', ', $events));

            return;
        }

        $components->warn('Failed to create the Resend webhook.');
        $components->error($this->errorMessage($response));
    }

    /**
     * Resend has no event for unsubscribes, so that tracking option has nothing
     * to subscribe to here.
     */
    protected function webhookEvents(): array
    {
        $trackingConfig = (array) config('mails.logging.tracking');

        return collect([
            'bounces' => ['email.bounced', 'email.delivery_delayed'],
            'clicks' => ['email.clicked'],
            'complaints' => ['email.complained'],
            'deliveries' => ['email.sent', 'email.delivered'],
            'opens' => ['email.opened'],
        ])
            ->filter(fn (array $events, string $type): bool => (bool) ($trackingConfig[$type] ?? false))
            ->flatten()
            ->all();
    }

    protected function errorMessage(Response $response): string
    {
        return $response->json('message') ?? $response->body();
    }

    public function verifyWebhookSignature(array $payload): bool
    {
        return true;
    }

    public function getUuidFromPayload(array $payload): ?string
    {
        return collect($payload['data']['headers'])
            ->where('name', config('mails.headers.uuid'))
            ->first()['value'] ?? null;
    }

    protected function getTimestampFromPayload(array $payload): string
    {
        return $payload['data']['created_at'] ?? now();
    }

    public function eventMapping(): array
    {
        return [
            EventType::ACCEPTED->value => ['type' => 'email.sent'],
            EventType::CLICKED->value => ['type' => 'email.clicked'],
            EventType::COMPLAINED->value => ['type' => 'email.complained'],
            EventType::DELIVERED->value => ['type' => 'email.delivered'],
            EventType::HARD_BOUNCED->value => ['type' => 'email.bounced'],
            EventType::OPENED->value => ['type' => 'email.opened'],
            EventType::SOFT_BOUNCED->value => ['type' => 'email.delivery_delayed'],
        ];
    }

    public function dataMapping(): array
    {
        return [
            'ip_address' => 'data.click.ipAddress',
            'link' => 'data.click.link',
            'user_agent' => 'data.click.userAgent',
        ];
    }

    public function attachUuidToMail(MessageSending $messageSending, string $uuid): MessageSending
    {
        $messageSending->message->getHeaders()->addTextHeader(config('mails.headers.uuid'), $uuid);

        return $messageSending;
    }

    public function unsuppressEmailAddress(string $address, ?int $stream_id = null): Response
    {
        // Resend doesn't support unsuppressing email addresses via API
        return new Response(new \GuzzleHttp\Psr7\Response(200, [], 'Not supported'));
    }
}
