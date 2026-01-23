<?php

namespace Backstage\Mails\Actions;

use Backstage\Mails\Facades\MailProvider;
use Backstage\Mails\Shared\AsAction;
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
