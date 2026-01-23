<?php

namespace Backstage\Mails\Commands;

use Backstage\Mails\Models\Mail;
use Illuminate\Console\Command;

class PruneMailCommand extends Command
{
    public $signature = 'mail:prune';

    public $description = 'Prune logged sent emails';

    public function handle(): int
    {
        if (! $this->shouldPrune()) {
            $this->components->warn('Pruning has been disabled in the config');

            return self::SUCCESS;
        }

        $this->call('model:prune', [
            '--model' => [Mail::class],
        ]);

        $this->comment('All done');

        return self::SUCCESS;
    }

    protected function shouldPrune(): bool
    {
        return config('mails.database.pruning.enabled', false);
    }
}
