<?php

namespace Backstage\Mails\Laravel\Drivers;

use Backstage\Mails\Laravel\Contracts\MailDriverContract;
use Backstage\Mails\Laravel\Enums\EventType;
use Backstage\Mails\Laravel\Enums\Provider;
use Backstage\Mails\Laravel\Models\Mail;
use Illuminate\Http\Client\Response;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class PostmarkDriver extends MailDriver implements MailDriverContract
{
    public function registerWebhooks($components): void
    {
        $trackingConfig = (array) config('mails.logging.tracking');

        $triggers = [
            'Open' => [
                'Enabled' => (bool) $trackingConfig['opens'],
                'PostFirstOpenOnly' => false,
            ],
            'Click' => [
                'Enabled' => (bool) $trackingConfig['clicks'],
            ],
            'Delivery' => [
                'Enabled' => (bool) $trackingConfig['deliveries'],
            ],
            'Bounce' => [
                'Enabled' => (bool) $trackingConfig['bounces'],
                'IncludeContent' => (bool) $trackingConfig['bounces'],
            ],
            'SpamComplaint' => [
                'Enabled' => (bool) $trackingConfig['complaints'],
                'IncludeContent' => (bool) $trackingConfig['complaints'],
            ],
            'SubscriptionChange' => [
                'Enabled' => (bool) $trackingConfig['unsubscribes'],
            ],
        ];

        $webhookUrl = URL::signedRoute('mails.webhook', ['provider' => Provider::POSTMARK]);

        $token = (string) config('services.postmark.token');

        $headers = [
            'Accept' => 'application/json',
            'X-Postmark-Server-Token' => $token,
        ];

        $broadcastStream = collect(Http::withHeaders($headers)->get('https://api.postmarkapp.com/message-streams')['MessageStreams'] ?? []);

        if ($broadcastStream->where('ID', 'broadcast')->count() === 0) {
            Http::withHeaders($headers)->post('https://api.postmarkapp.com/message-streams', [
                'ID' => 'broadcast',
                'Name' => 'Broadcasts',
                'Description' => 'Default Broadcast Stream',
            ]);
        } else {
            $components->info('Broadcast stream already exists');
        }

        $outboundWebhooks = collect(Http::withHeaders($headers)->get('https://api.postmarkapp.com/webhooks?MessageStream=outbound')['Webhooks'] ?? []);

        if ($outboundWebhooks->where('Url', $webhookUrl)->count() === 0) {
            $response = Http::withHeaders($headers)->post('https://api.postmarkapp.com/webhooks?MessageStream=outbound', [
                'Url' => $webhookUrl,
                'Triggers' => $triggers,
            ]);

            if ($response->ok()) {
                $components->info('Created Postmark webhook for outbound stream');
            } else {
                $components->error('Failed to create Postmark webhook for outbound stream');
            }
        } else {
            $components->info('Outbound webhook already exists');
        }

        $broadcastWebhooks = collect(Http::withHeaders($headers)->get('https://api.postmarkapp.com/webhooks?MessageStream=broadcast')['Webhooks'] ?? []);

        if ($broadcastWebhooks->where('Url', $webhookUrl)->count() === 0) {
            $response = Http::withHeaders($headers)->post('https://api.postmarkapp.com/webhooks?MessageStream=broadcast', [
                'Url' => $webhookUrl,
                'MessageStream' => 'broadcast',
                'Triggers' => $triggers,
            ]);

            if ($response->ok()) {
                $components->info('Created Postmark webhook for broadcast stream');
            } else {
                $components->error('Failed to create Postmark webhook for broadcast stream');
            }
        } else {
            $components->info('Broadcast webhook already exists');
        }
    }

    public function verifyWebhookSignature(array $payload): bool
    {
        return true;
    }

    public function attachUuidToMail(MessageSending $messageSending, string $uuid): MessageSending
    {
        $messageSending->message->getHeaders()->addTextHeader('X-PM-Metadata-'.config('mails.headers.uuid'), $uuid);

        return $messageSending;
    }

    public function getUuidFromPayload(array $payload): ?string
    {
        return $payload['Metadata'][$this->uuidHeaderName] ??
            $payload['Metadata'][strtolower($this->uuidHeaderName)] ??
            $payload['Metadata'][strtoupper($this->uuidHeaderName)] ??
            null;
    }

    protected function getTimestampFromPayload(array $payload): string
    {
        return $payload['DeliveredAt'] ?? $payload['BouncedAt'] ?? $payload['ReceivedAt'] ?? now();
    }

    public function eventMapping(): array
    {
        return [
            EventType::CLICKED->value => ['RecordType' => 'Click'],
            EventType::COMPLAINED->value => ['RecordType' => 'SpamComplaint'],
            EventType::DELIVERED->value => ['RecordType' => 'Delivery'],
            EventType::HARD_BOUNCED->value => ['RecordType' => 'Bounce', 'Type' => 'HardBounce'],
            EventType::OPENED->value => ['RecordType' => 'Open'],
            EventType::SOFT_BOUNCED->value => ['RecordType' => 'Bounce', 'Type' => 'SoftBounce'],
            EventType::UNSUBSCRIBED->value => ['RecordType' => 'SubscriptionChange'],

            // Subscription changes
            EventType::UNSUBSCRIBE->value => ['RecordType' => 'SubscriptionChange', 'Type' => 'Unsubscribe'],
            EventType::SUBSCRIBE->value => ['RecordType' => 'SubscriptionChange', 'Type' => 'Subscribe'],

            // Bounce subtypes: Postmark delivers these with RecordType "Bounce" and the
            // specific kind in the "Type" field (see https://postmarkapp.com/developer/api/bounce-api#bounce-types).
            EventType::TRANSIENT->value => ['RecordType' => 'Bounce', 'Type' => 'Transient'],
            EventType::AUTO_RESPONDER->value => ['RecordType' => 'Bounce', 'Type' => 'AutoResponder'],
            EventType::ADDRESS_CHANGE->value => ['RecordType' => 'Bounce', 'Type' => 'AddressChange'],
            EventType::DNS_ERROR->value => ['RecordType' => 'Bounce', 'Type' => 'DnsError'],
            EventType::SPAM_NOTIFICATION->value => ['RecordType' => 'Bounce', 'Type' => 'SpamNotification'],
            EventType::OPEN_RELAY_TEST->value => ['RecordType' => 'Bounce', 'Type' => 'OpenRelayTest'],
            EventType::SOFT_BOUNCE->value => ['RecordType' => 'Bounce', 'Type' => 'SoftBounce'],
            EventType::VIRUS_NOTIFICATION->value => ['RecordType' => 'Bounce', 'Type' => 'VirusNotification'],
            EventType::CHALLENGE_VERIFICATION->value => ['RecordType' => 'Bounce', 'Type' => 'ChallengeVerification'],
            EventType::BAD_EMAIL_ADDRESS->value => ['RecordType' => 'Bounce', 'Type' => 'BadEmailAddress'],
            EventType::SPAM_COMPLAINT->value => ['RecordType' => 'Bounce', 'Type' => 'SpamComplaint'],
            EventType::MANUALLY_DEACTIVATED->value => ['RecordType' => 'Bounce', 'Type' => 'ManuallyDeactivated'],
            EventType::UNCONFIRMED->value => ['RecordType' => 'Bounce', 'Type' => 'Unconfirmed'],
            EventType::BLOCKED->value => ['RecordType' => 'Bounce', 'Type' => 'Blocked'],
            EventType::SMTP_API_ERROR->value => ['RecordType' => 'Bounce', 'Type' => 'SMTPApiError'],
            EventType::INBOUND_ERROR->value => ['RecordType' => 'Bounce', 'Type' => 'InboundError'],
            EventType::DMARC_POLICY->value => ['RecordType' => 'Bounce', 'Type' => 'DMARCPolicy'],
            EventType::TEMPLATE_RENDERING_FAILED->value => ['RecordType' => 'Bounce', 'Type' => 'TemplateRenderingFailed'],
            EventType::UNKNOWN->value => ['RecordType' => 'Bounce', 'Type' => 'Unknown'],
        ];
    }

    public function dataMapping(): array
    {
        return [
            'browser' => 'Client.Family',
            'city' => 'City',
            'country_code' => 'Geo.CountryISOCode',
            'ip_address' => 'Geo.IP',
            'link' => 'OriginalLink',
            'os' => 'OS.Family',
            'platform' => 'Platform',
            'tag' => 'Tag',
            'user_agent' => 'UserAgent',
        ];
    }

    public function unsuppressEmailAddress(string $address, ?int $stream_id = null): Response
    {
        $pendingRequest = Http::asJson()
            ->withHeaders([
                'X-Postmark-Server-Token' => config('services.postmark.token'),
            ])
            ->baseUrl('https://api.postmarkapp.com/');

        return $pendingRequest->post('message-streams/'.$stream_id.'/suppressions/delete', [
            'Suppressions' => [['emailAddress' => $address]],
        ]);
    }

    public function transient(Mail $mail, string $timestamp): void
    {
        $this->softBounced($mail, $timestamp);
    }

    public function unsubscribe(Mail $mail, string $timestamp): void
    {
        $this->unsubscribed($mail, $timestamp);
    }

    public function dnsError(Mail $mail, string $timestamp): void
    {
        $this->softBounced($mail, $timestamp);
    }

    public function spamNotification(Mail $mail, string $timestamp): void
    {
        $this->complained($mail, $timestamp);
    }

    public function softBounce(Mail $mail, string $timestamp): void
    {
        $this->softBounced($mail, $timestamp);
    }

    public function virusNotification(Mail $mail, string $timestamp): void
    {
        $this->complained($mail, $timestamp);
    }

    public function challengeVerification(Mail $mail, string $timestamp): void
    {
        $this->softBounced($mail, $timestamp);
    }

    public function badEmailAddress(Mail $mail, string $timestamp): void
    {
        $this->hardBounced($mail, $timestamp);
    }

    public function spamComplaint(Mail $mail, string $timestamp): void
    {
        $this->complained($mail, $timestamp);
    }

    public function blocked(Mail $mail, string $timestamp): void
    {
        $this->hardBounced($mail, $timestamp);
    }

    public function smtpApiError(Mail $mail, string $timestamp): void
    {
        $this->hardBounced($mail, $timestamp);
    }

    public function dmarcPolicy(Mail $mail, string $timestamp): void
    {
        $this->hardBounced($mail, $timestamp);
    }

    public function templateRenderingFailed(Mail $mail, string $timestamp): void
    {
        $this->hardBounced($mail, $timestamp);
    }
}
