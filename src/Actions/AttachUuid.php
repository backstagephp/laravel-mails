<?php

namespace Backstage\Mails\Actions;

use Backstage\Mails\Facades\MailProvider;
use Backstage\Mails\Shared\AsAction;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Str;

class AttachUuid
{
    use AsAction;

    public function handle(MessageSending $messageSending): MessageSending
    {
        $provider = $this->getProvider($messageSending);

        if (! $this->shouldTrackMails($provider)) {
            return $messageSending;
        }

        $uuid = Str::uuid()->toString();

        $messageSending->message->getHeaders()->addTextHeader(config('mails.headers.uuid'), $uuid);

        return MailProvider::with($provider)->attachUuidToMail($messageSending, $uuid);
    }

    public function getProvider(MessageSending $messageSending): string
    {
        return config('mail.mailers.' . $messageSending->data['mailer'] . '.transport') ?? $messageSending->data['mailer'];
    }

    public function shouldTrackMails(string $provider): bool
    {
        return $this->trackingEnabled() &&
            $this->driverExistsForProvider($provider);
    }

    public function driverExistsForProvider(string $provider): bool
    {
        return class_exists('Backstage\\Mails\\Drivers\\' . ucfirst($provider) . 'Driver');
    }

    public function trackingEnabled(): bool
    {
        return (bool) config('mails.logging.tracking.bounces') ||
            (bool) config('mails.logging.tracking.clicks') ||
            (bool) config('mails.logging.tracking.complaints') ||
            (bool) config('mails.logging.tracking.deliveries') ||
            (bool) config('mails.logging.tracking.opens') ||
            (bool) config('mails.logging.tracking.unsubscribes');
    }
}
