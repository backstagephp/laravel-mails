<?php

namespace Backstage\Mails\Listeners;

use Backstage\Mails\Actions\LogMail;
use Illuminate\Mail\Events\MessageSending;

class LogSendingMail
{
    public function handle(MessageSending $messageSending): void
    {
        (new LogMail)($messageSending);
    }
}
