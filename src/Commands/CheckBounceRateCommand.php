<?php

namespace Backstage\Mails\Commands;

use Backstage\Mails\Actions\SendHighBounceRateNotifications;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class CheckBounceRateCommand extends Command
{
    protected $signature = 'mail:bounce-rate';

    protected $description = 'Check if the bounce rate is higher than the configured limit '.
        'and send a notification if it is.';

    public function handle(): int
    {
        /**
         * @var class-string<Model> $mail
         * @var int $threshold
         * @var int $retain
         */
        [$mail, $threshold, $retain] = array_values(config()->get([
            'mails.models.mail',
            'mails.events.bouncerate.treshold',
            'mails.events.bouncerate.retain',
        ]));

        $until = now()->subDays($retain);

        $all = call_user_func_array([$mail, 'query'], [])
            ->whereTime('created_at', '<', $until)
            ->count();

        if ($all < 1) {
            $this->components->error('No mails have been sent.');

            return self::FAILURE;
        }

        $bounced = call_user_func_array([$mail, 'query'], [])
            ->whereTime('created_at', '<', $until)
            ->whereNotNull('hard_bounced_at')
            ->orWhereNotNull('soft_bounced_at')
            ->count();

        $rate = round(($bounced / $all) * 100, 2);

        if ($rate > $threshold) {
            (new SendHighBounceRateNotifications)($rate, $threshold);

            $this->components->error(
                "Bounce rate is {$rate}%, that's higher than the configured threshold of {$threshold}%",
            );

            return self::FAILURE;
        }

        $this->components->info("Bounce rate is {$rate}%");

        return self::SUCCESS;
    }
}
