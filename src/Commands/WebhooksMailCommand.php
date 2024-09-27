<?php

namespace Vormkracht10\Mails\Commands;

use Illuminate\Console\Command;
use Vormkracht10\Mails\Actions\RegisterWebhooks;
use Illuminate\Contracts\Console\PromptsForMissingInput;

class WebhooksMailCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'mail:webhooks {provider}';

    public $description = 'Register event webhooks for email provider';

    public function handle(): int
    {
        (new RegisterWebhooks)(
            provider: $this->argument('provider'),
            components: $this->components
        );

        return self::SUCCESS;
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'provider' => 'Which email provider would you like to register webhooks for?',
        ];
    }
}
