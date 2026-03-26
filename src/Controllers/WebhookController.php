<?php

namespace Backstage\Mails\Laravel\Controllers;

use Backstage\Mails\Laravel\Enums\Provider;
use Backstage\Mails\Laravel\Jobs\ProcessWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController
{
    public function __invoke(Request $request, string $provider): Response
    {
        if (! Provider::tryFrom($provider)) {
            return response('Unknown provider.', status: 400);
        }

        ProcessWebhookJob::dispatch($provider, $request->all());

        return response('Event processed.', status: 202);
    }
}
