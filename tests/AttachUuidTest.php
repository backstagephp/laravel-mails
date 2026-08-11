<?php

use Backstage\Mails\Laravel\Actions\AttachUuid;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email;

function messageSending(string $mailer): MessageSending
{
    $email = (new Email)
        ->from('local@computer.nl')
        ->to('mark@vormkracht10.nl')
        ->subject('Test')
        ->text('Text');

    return new MessageSending($email, ['mailer' => $mailer]);
}

it('finds the driver of every supported provider', function (string $provider): void {
    expect((new AttachUuid)->driverExistsForProvider($provider))->toBeTrue();
})->with(['mailgun', 'postmark', 'resend', 'ses', 'ses-v2']);

it('does not find a driver for unsupported providers', function (): void {
    expect((new AttachUuid)->driverExistsForProvider('sendgrid'))->toBeFalse();
});

it('attaches a uuid to mails sent through a supported provider', function (): void {
    config()->set('mail.mailers.mailgun.transport', 'mailgun');

    $event = messageSending('mailgun');

    (new AttachUuid)->handle($event);

    $uuid = $event->message->getHeaders()->get(config('mails.headers.uuid'))?->getBodyAsString();

    expect($uuid)->toBeString()->not->toBeEmpty();

    expect($event->message->getHeaders()->get('X-Mailgun-Variables')->getBodyAsString())
        ->toBe(json_encode([config('mails.headers.uuid') => $uuid]));
});

it('does not attach a uuid when tracking is disabled', function (): void {
    config()->set('mail.mailers.mailgun.transport', 'mailgun');
    config()->set('mails.logging.tracking', array_fill_keys(
        ['bounces', 'clicks', 'complaints', 'deliveries', 'opens', 'unsubscribes'],
        false,
    ));

    $event = messageSending('mailgun');

    (new AttachUuid)->handle($event);

    expect($event->message->getHeaders()->has(config('mails.headers.uuid')))->toBeFalse();
});
