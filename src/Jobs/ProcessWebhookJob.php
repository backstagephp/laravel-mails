<?php

namespace Backstage\Mails\Laravel\Jobs;

use Backstage\Mails\Laravel\Enums\Provider;
use Backstage\Mails\Laravel\Events\MailEvent;
use Backstage\Mails\Laravel\Facades\MailProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $provider,
        public array $payload
    ) {}

    public function handle(): void
    {
        if (! in_array($this->provider, array_column(Provider::cases(), 'value'))) {
            return;
        }

        if (! MailProvider::with($this->provider)->verifyWebhookSignature($this->payload)) {
            return;
        }

        MailEvent::dispatch($this->provider, $this->payload);
    }
}
