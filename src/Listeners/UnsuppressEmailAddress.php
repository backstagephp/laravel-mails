<?php

namespace Backstage\Mails\Laravel\Listeners;

use Backstage\Mails\Laravel\Events\MailUnsuppressed;
use Backstage\Mails\Laravel\Facades\MailProvider;

class UnsuppressEmailAddress
{
    public function handle(MailUnsuppressed $mailUnsuppressed): void
    {
        MailProvider::with(driver: $mailUnsuppressed->mailer)
            ->unsuppressEmailAddress(
                address: $mailUnsuppressed->emailAddress,
                stream_id: $mailUnsuppressed->stream_id ?? null
            );
    }
}
