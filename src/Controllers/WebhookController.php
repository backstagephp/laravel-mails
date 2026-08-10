<?php

namespace Backstage\Mails\Laravel\Controllers;

use Backstage\Mails\Laravel\Jobs\ProcessWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController
{
    public function __invoke(Request $request, string $provider): Response
    {
        $payload = $request->all();

        // Amazon SNS posts its JSON with Content-Type text/plain, which the
        // request parser ignores, leaving only the query string (the webhook
        // route's signature). Decode the raw body ourselves in that case.
        if (! $request->isJson() && $request->request->count() === 0) {
            $decoded = json_decode($request->getContent(), true);

            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        ProcessWebhookJob::dispatch($provider, $payload);

        return response('Event processed.', status: 202);
    }
}
