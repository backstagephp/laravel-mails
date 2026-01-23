<?php

namespace Backstage\Mails\Listeners;

use Backstage\Mails\Actions\AttachUuid;
use Illuminate\Mail\Events\MessageSending;

class AttachMailLogUuid
{
    public function handle(MessageSending $messageSending): void
    {
        (new AttachUuid)($messageSending);
    }
}
