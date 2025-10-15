<?php

namespace Backstage\Mails\Listeners;

use Backstage\Mails\Events\MailUnsuppressed;
use Backstage\Mails\Facades\MailProvider;

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
