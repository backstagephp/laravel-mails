<?php

use Backstage\Mails\Laravel\Enums\Provider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

use function Pest\Laravel\artisan;

it('registers webhooks for postmark', function (): void {
    config()->set('services.postmark.token', 'postmark-token');

    Http::fake([
        'api.postmarkapp.com/message-streams' => Http::response(['MessageStreams' => [['ID' => 'broadcast']]]),
        'api.postmarkapp.com/webhooks*' => Http::response(['Webhooks' => []]),
    ]);

    artisan('mail:webhooks', ['provider' => 'postmark'])->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.postmarkapp.com/webhooks?MessageStream=outbound');
});

it('registers webhooks for mailgun', function (): void {
    config()->set('services.mailgun.secret', 'mailgun-secret');
    config()->set('services.mailgun.domain', 'mail.example.com');

    Http::fake(['api.mailgun.net/*' => Http::response(['webhook' => []])]);

    artisan('mail:webhooks', ['provider' => 'mailgun'])->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'mail.example.com/webhooks'));
});

it('registers webhooks for resend', function (): void {
    config()->set('services.resend.key', 'resend-key');

    Http::fake(['api.resend.com/webhooks' => Http::response(['data' => []])]);

    artisan('mail:webhooks', ['provider' => 'resend'])->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.resend.com/webhooks'
        && $request['endpoint'] === URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND])
        && $request['events'] === [
            'email.bounced',
            'email.delivery_delayed',
            'email.clicked',
            'email.complained',
            'email.sent',
            'email.delivered',
            'email.opened',
        ]);
});

it('only subscribes resend to the events that are tracked', function (): void {
    config()->set('services.resend.key', 'resend-key');
    config()->set('mails.logging.tracking', ['opens' => true] + array_fill_keys(
        ['bounces', 'clicks', 'complaints', 'deliveries', 'unsubscribes'],
        false,
    ));

    Http::fake(['api.resend.com/webhooks' => Http::response(['data' => []])]);

    artisan('mail:webhooks', ['provider' => 'resend'])->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request['events'] === ['email.opened']);
});

it('does not register a second resend webhook for the same endpoint', function (): void {
    config()->set('services.resend.key', 'resend-key');

    // A rotated app key changes the signature, but the webhook is still ours.
    $endpoint = URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND]).'-outdated-signature';

    Http::fake(['api.resend.com/webhooks' => Http::response(['data' => [['endpoint' => $endpoint]]])]);

    artisan('mail:webhooks', ['provider' => 'resend'])->assertSuccessful();

    Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST');
});

it('shows the webhook url for resend when no api key is configured', function (): void {
    config()->set('services.resend.key', null);

    // The signature makes the url too long to survive the console's line
    // wrapping, so assert on the part that identifies the endpoint.
    $webhookUrl = URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND]);

    artisan('mail:webhooks', ['provider' => 'resend'])
        ->expectsOutputToContain(Str::before($webhookUrl, '?'))
        ->assertSuccessful();
});

it('reports a failing resend api instead of claiming success', function (string $failing, string $message): void {
    config()->set('services.resend.key', 'resend-key');

    Http::fake(fn (Request $request) => $request->method() === $failing
        ? Http::response(['message' => $message], 401)
        : Http::response(['data' => []]));

    artisan('mail:webhooks', ['provider' => 'resend'])
        ->expectsOutputToContain($message)
        ->assertSuccessful();
})->with([
    'listing' => ['GET', 'API key is invalid'],
    'creating' => ['POST', 'Endpoint is not reachable'],
]);

it('fails gracefully registering webhooks for ses when unconfigured', function (): void {
    // Whether the AWS SDK is missing or the ses services config is empty, the
    // command should explain the problem instead of crashing.
    artisan('mail:webhooks', ['provider' => 'ses'])->assertSuccessful();
});
