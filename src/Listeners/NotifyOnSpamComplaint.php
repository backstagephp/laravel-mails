<?php

namespace Backstage\Mails\Listeners;

use Backstage\Mails\Events\MailComplained;
use Backstage\Mails\Notifications\SpamComplaintNotification;
use Backstage\Mails\Traits\SendsNotifications;

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
