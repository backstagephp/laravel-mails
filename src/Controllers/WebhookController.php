<?php

namespace Backstage\Mails\Controllers;

use Backstage\Mails\Enums\Provider;
use Backstage\Mails\Events\MailEvent;
use Backstage\Mails\Facades\MailProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController
{
    public function __invoke(Request $request, string $provider): Response
    {
        dispatch(fn() => static::handle($request, $provider));

        return response('Event processed.', status: 202);
    }

    protected static function handle($request,  $provider): void
    {
        if (! in_array($provider, array_column(Provider::cases(), 'value'))) {
            return;
        }

        if (! MailProvider::with($provider)->verifyWebhookSignature($request->all())) {
            return;
        }

        MailEvent::dispatch($provider, $request->except('signature'));
    }
}
