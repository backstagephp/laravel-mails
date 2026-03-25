<?php

namespace Backstage\Mails\Laravel\Listeners;

use Backstage\Mails\Laravel\Events\MailComplained;
use Backstage\Mails\Laravel\Notifications\SpamComplaintNotification;
use Backstage\Mails\Laravel\Traits\SendsNotifications;

class NotifyOnSpamComplaint
{
    use SendsNotifications;

    public function handle(MailComplained $mailComplained): void
    {
        if (! $channels = config('mails.events.complaint.notify')) {
            return;
        }

        $spamComplaintNotification = new SpamComplaintNotification($mailComplained->mailEvent->mail);

        $this->send($spamComplaintNotification, $channels);
    }
}
