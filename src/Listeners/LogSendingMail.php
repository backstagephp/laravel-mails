<?php

namespace Vormkracht10\Mails\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Vormkracht10\Mails\Actions\LogMail;

class LogSendingMail
{
    public function handle(MessageSending $event): void
    {
        (new LogMail)($event);
    }
}
