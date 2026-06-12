<?php

namespace Backstage\Mails\Laravel\Listeners;

use Backstage\Mails\Laravel\Events\MailHardBounced;
use Backstage\Mails\Laravel\Notifications\BounceNotification;
use Backstage\Mails\Laravel\Traits\SendsNotifications;

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
