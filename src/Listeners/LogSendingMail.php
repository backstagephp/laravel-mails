<?php

namespace Backstage\Mails\Laravel\Listeners;

use Backstage\Mails\Laravel\Actions\LogMail;
use Illuminate\Mail\Events\MessageSending;

class LogSendingMail
{
    public function handle(MessageSending $messageSending): void
    {
        (new LogMail)($messageSending);
    }
}
