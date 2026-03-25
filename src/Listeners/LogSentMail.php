<?php

namespace Backstage\Mails\Laravel\Listeners;

use Backstage\Mails\Laravel\Actions\LogMail;
use Illuminate\Mail\Events\MessageSent;

class LogSentMail
{
    public function handle(MessageSent $messageSent): void
    {
        (new LogMail)($messageSent);
    }
}
