<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

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
    config()->set('services.resend.api_key', 'resend-key');

    Http::fake(['api.resend.com/*' => Http::response([])]);

    artisan('mail:webhooks', ['provider' => 'resend'])->assertSuccessful();
});

it('fails gracefully registering webhooks for ses when unconfigured', function (): void {
    // Whether the AWS SDK is missing or the ses services config is empty, the
    // command should explain the problem instead of crashing.
    artisan('mail:webhooks', ['provider' => 'ses'])->assertSuccessful();
});
