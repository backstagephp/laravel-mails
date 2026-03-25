<?php

namespace Backstage\Mails\Laravel\Events;

use Backstage\Mails\Laravel\Models\Mail;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MailLogged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Mail $mail
    ) {}
}
