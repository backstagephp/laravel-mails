<?php

namespace Backstage\Mails\Listeners;

use Backstage\Mails\Actions\LogMail;
use Illuminate\Mail\Events\MessageSent;

class LogSentMail
{
    public function handle(MessageSent $messageSent): void
    {
        (new LogMail)($messageSent);
    }
}
