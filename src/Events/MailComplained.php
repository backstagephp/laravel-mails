<?php

namespace Backstage\Mails\Laravel\Events;

use Backstage\Mails\Laravel\Models\MailEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MailComplained
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public MailEvent $mailEvent
    ) {}
}
