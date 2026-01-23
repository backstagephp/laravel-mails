<?php

namespace Backstage\Mails\Listeners;

use Backstage\Mails\Events\MailEvent;
use Backstage\Mails\Facades\MailProvider;

class LogMailEvent
{
    public function handle(MailEvent $mailEvent): void
    {
        $mail = MailProvider::with($mailEvent->provider)->getMailFromPayload($mailEvent->payload);

        if (! $mail) {
            return;
        }

        if (config('mails.webhooks.queue')) {
            $this->dispatch($mailEvent->provider, $mailEvent->payload);

            return;
        }

        $this->record($mailEvent->provider, $mailEvent->payload);
    }

    private function record(string $provider, array $payload): void
    {
        MailProvider::with($provider)
            ->logMailEvent($payload);
    }

    private function dispatch(string $provider, array $payload): void
    {
        dispatch(fn () => $this->record($provider, $payload));
    }
}
