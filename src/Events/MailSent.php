<?php

namespace Backstage\Mails\Laravel\Events;

use Backstage\Mails\Laravel\Models\Mail;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A logged mail was accepted by the transport.
 *
 * Mails are logged on MessageSending, before the transport is called, so a
 * MailLogged mail may still be rejected. This event only follows once the
 * mail got its sent_at.
 */
class MailSent
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
