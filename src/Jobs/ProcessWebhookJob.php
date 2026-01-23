<?php

namespace Backstage\Mails\Jobs;

use Backstage\Mails\Enums\Provider;
use Backstage\Mails\Events\MailEvent;
use Backstage\Mails\Facades\MailProvider;
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
