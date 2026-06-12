<?php

namespace Backstage\Mails\Laravel\Listeners;

use Backstage\Mails\Laravel\Actions\AttachUuid;
use Illuminate\Mail\Events\MessageSending;

class AttachMailLogUuid
{
    public function handle(MessageSending $messageSending): void
    {
        (new AttachUuid)($messageSending);
    }
}
