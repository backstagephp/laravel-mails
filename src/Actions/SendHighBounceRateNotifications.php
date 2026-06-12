<?php

namespace Backstage\Mails\Laravel\Actions;

use Backstage\Mails\Laravel\Notifications\HighBounceRateNotification;
use Backstage\Mails\Laravel\Shared\AsAction;
use Backstage\Mails\Laravel\Traits\SendsNotifications;

class SendHighBounceRateNotifications
{
    use AsAction;
    use SendsNotifications;

    /**
     * @param  float|int  $rate
     * @param  float|int  $threshold
     */
    public function handle($rate, $threshold): bool
    {
        if (! $channels = config('mails.events.bouncerate.notify')) {
            return false;
        }

        $highBounceRateNotification = new HighBounceRateNotification($rate, $threshold);

        $this->send($highBounceRateNotification, $channels);

        return true;
    }
}
