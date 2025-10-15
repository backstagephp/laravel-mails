<?php

namespace Backstage\Mails\Commands;

use Backstage\Mails\Models\Mail;
use Illuminate\Console\Command;

class MonitorMailCommand extends Command
{
    public $signature = 'mail:monitor';

    public $description = 'Monitor for sent mails';

    public function handle(): int
    {
        if (null !== ($bounceRateTreshold = config('mails.events.bouncerate.treshold')) && $this->getBounceRate() >= $bounceRateTreshold) {
            // TODO: notify

        }

        if (null !== ($deliveryRateTreshold = config('mails.events.deliveryrate.treshold')) && $this->getDeliveryRate() <= $deliveryRateTreshold) {

            // TODO: notify

        }

        return self::SUCCESS;
    }

    public function getBounceRate(): float
    {
        $bounces = Mail::whereNotNull('soft_bounced_at')->orWhereNotNull('hard_bounced_at')->count();
        $total = Mail::count();

        return ($bounces / $total) * 100;
    }

    public function getDeliveryRate(): float
    {
        $deliveries = Mail::whereNotNull('delivered_at')->count();
        $total = Mail::count();

        return ($deliveries / $total) * 100;
    }
}
