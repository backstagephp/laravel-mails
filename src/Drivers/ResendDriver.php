<?php

namespace Backstage\Mails\Drivers;

use Backstage\Mails\Contracts\MailDriverContract;
use Backstage\Mails\Enums\EventType;
use Illuminate\Mail\Events\MessageSending;

class ResendDriver extends MailDriver implements MailDriverContract
{
    public function registerWebhooks($components): void
    {
        $components->warn("Resend doesn't allow registering webhooks via the API. ");
        $components->info("Please register your webhooks manually in the Resend dashboard.");
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

    public function attachUuidToMail(MessageSending $event, string $uuid): MessageSending
    {
        $event->message->getHeaders()->addTextHeader(config('mails.headers.uuid'), $uuid);

        return $event;
    }
}
