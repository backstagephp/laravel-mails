<?php

namespace Backstage\Mails\Actions;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\View\Components\Factory;
use Backstage\Mails\Facades\MailProvider;
use Backstage\Mails\Shared\AsAction;

class RegisterWebhooks
{
    use AsAction, InteractsWithIO;

    public function handle(string $provider, Factory $components): void
    {
        MailProvider::with($provider)->registerWebhooks(
            components: $components
        );
    }
}
