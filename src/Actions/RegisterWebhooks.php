<?php

namespace Backstage\Mails\Laravel\Actions;

use Backstage\Mails\Laravel\Facades\MailProvider;
use Backstage\Mails\Laravel\Shared\AsAction;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\View\Components\Factory;

class RegisterWebhooks
{
    use AsAction;
    use InteractsWithIO;

    public function handle(string $provider, Factory $factory): void
    {
        MailProvider::with($provider)->registerWebhooks(
            components: $factory
        );
    }
}
