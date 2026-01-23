<?php

namespace Backstage\Mails\Listeners;

use Backstage\Mails\Events\MailHardBounced;
use Backstage\Mails\Notifications\BounceNotification;
use Backstage\Mails\Traits\SendsNotifications;

class NotifyOnBounce
{
    use SendsNotifications;

    public function handle(MailHardBounced $mailHardBounced): void
    {
        if (! $channels = config('mails.events.bounce.notify')) {
            return;
        }

        $bounceNotification = new BounceNotification($mailHardBounced->mailEvent->mail);

        $this->send($bounceNotification, $channels);
    }
}
