<?php

namespace Backstage\Mails\Drivers;

use Backstage\Mails\Contracts\MailDriverContract;
use Backstage\Mails\Enums\EventType;
use Backstage\Mails\Enums\Provider;
use Illuminate\Http\Client\Response;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class ResendDriver extends MailDriver implements MailDriverContract
{
    public function registerWebhooks($components): void
    {
        $trackingConfig = (array) config('mails.logging.tracking');

        $apiKey = (string) config('services.resend.key');
        $webhookUrl = URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND]);

        $events = [];

        if ((bool) $trackingConfig['deliveries']) {
            $events[] = 'email.sent';
            $events[] = 'email.delivered';
        }

        if ((bool) $trackingConfig['opens']) {
            $events[] = 'email.opened';
        }

        if ((bool) $trackingConfig['clicks']) {
            $events[] = 'email.clicked';
        }

        if ((bool) $trackingConfig['bounces']) {
            $events[] = 'email.bounced';
            $events[] = 'email.delivery_delayed';
        }

        if ((bool) $trackingConfig['complaints']) {
            $events[] = 'email.complained';
        }

        $existingWebhooks = Http::withToken($apiKey)
            ->get('https://api.resend.com/webhooks');

        $existing = collect($existingWebhooks->json('data') ?? [])
            ->firstWhere('endpoint', $webhookUrl);

        if ($existing) {
            $components->info('Resend webhook already exists for this endpoint.');
            $components->info('Webhook ID: '.$existing['id']);

            return;
        }

        $response = Http::withToken($apiKey)
            ->post('https://api.resend.com/webhooks', [
                'endpoint' => $webhookUrl,
                'events' => $events,
            ]);

        if ($response->successful()) {
            $signingSecret = $response->json('signing_secret');

            $components->info('Created Resend webhook successfully.');
            $components->warn('Save this signing secret to your config as services.resend.webhook_signing_secret:');
            $components->info($signingSecret);
        } else {
            $components->error('Failed to create Resend webhook.');
            $components->error($response->json('message') ?? $response->body());
        }
    }

    public function verifyWebhookSignature(array $payload): bool
    {
        if (app()->runningUnitTests()) {
            return true;
        }

        $secret = (string) config('services.resend.webhook_signing_secret');

        if (empty($secret)) {
            return true;
        }

        $request = request();

        $svixId = $request->header('svix-id');
        $svixTimestamp = $request->header('svix-timestamp');
        $svixSignature = $request->header('svix-signature');

        if (empty($svixId) || empty($svixTimestamp) || empty($svixSignature)) {
            return false;
        }

        // Verify timestamp is within tolerance (5 minutes)
        $tolerance = 5 * 60;
        if (abs(time() - (int) $svixTimestamp) > $tolerance) {
            return false;
        }

        // Strip the whsec_ prefix and base64 decode the secret
        $secretBytes = base64_decode(str_replace('whsec_', '', $secret));

        // Construct the signed content: msg_id.timestamp.body
        $body = (string) $request->getContent();
        $signedContent = "{$svixId}.{$svixTimestamp}.{$body}";

        // Compute expected signature using HMAC-SHA256
        $expectedSignature = base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

        // The svix-signature header may contain multiple signatures separated by spaces
        $signatures = explode(' ', $svixSignature);

        foreach ($signatures as $signature) {
            // Strip version prefix (v1,)
            $parts = explode(',', $signature, 2);
            $signatureValue = $parts[1] ?? $parts[0];

            if (hash_equals($expectedSignature, $signatureValue)) {
                return true;
            }
        }

        return false;
    }

    public function getUuidFromPayload(array $payload): ?string
    {
        return collect($payload['data']['headers'] ?? [])
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
            'tag' => 'data.tags',
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
