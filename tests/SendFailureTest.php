<?php

use Aws\Command;
use Aws\Exception\AwsException;
use Backstage\Mails\Laravel\Enums\Provider;
use Backstage\Mails\Laravel\Enums\SendFailure;
use Backstage\Mails\Laravel\Facades\MailProvider;
use Symfony\Component\Mailer\Exception\TransportException;

function sesSendException(string $awsErrorCode): TransportException
{
    return new TransportException(
        'Request to AWS SES API failed. Reason: AWS error.',
        0,
        new AwsException('AWS error', new Command('SendRawEmail'), ['code' => $awsErrorCode]),
    );
}

it('classifies postmark and mailgun send failures', function (Provider $provider, string $message, SendFailure $expected): void {
    expect(MailProvider::with($provider->value)->getSendFailure(new TransportException($message)))->toBe($expected);
})->with([
    'postmark inactive recipient' => [Provider::POSTMARK, 'Unable to send an email: You tried to send to recipient(s) that have been marked as inactive. (code 406).', SendFailure::INACTIVE_RECIPIENT],
    'postmark invalid email request' => [Provider::POSTMARK, "Unable to send an email: Error parsing 'To'. (code 300).", SendFailure::PERMANENT],
    'postmark bad api token' => [Provider::POSTMARK, 'Unable to send an email: No Account or Server API tokens were supplied. (code 10).', SendFailure::PERMANENT],
    'postmark service unavailable' => [Provider::POSTMARK, 'Unable to send an email: Service Unavailable (code 503).', SendFailure::TRANSIENT],
    'postmark connection error' => [Provider::POSTMARK, 'Connection timed out', SendFailure::TRANSIENT],
    'mailgun bad request' => [Provider::MAILGUN, "Unable to send an email: 'to' parameter is not a valid address. (code 400).", SendFailure::PERMANENT],
    'mailgun domain not found' => [Provider::MAILGUN, 'Unable to send an email: Domain not found: example.com (code 404).', SendFailure::PERMANENT],
    'mailgun rate limited' => [Provider::MAILGUN, 'Unable to send an email: Too many requests (code 429).', SendFailure::TRANSIENT],
    'mailgun has no inactive recipient code' => [Provider::MAILGUN, 'Unable to send an email: Not acceptable (code 406).', SendFailure::TRANSIENT],
    'resend is always retried' => [Provider::RESEND, 'Request to Resend API failed. Reason: Invalid `to` field.', SendFailure::TRANSIENT],
]);

it('classifies ses send failures by their aws error code', function (Provider $provider, string $awsErrorCode, SendFailure $expected): void {
    expect(MailProvider::with($provider->value)->getSendFailure(sesSendException($awsErrorCode)))->toBe($expected);
})->with([
    'ses message rejected' => [Provider::SES, 'MessageRejected', SendFailure::PERMANENT],
    'ses throttled' => [Provider::SES, 'Throttling', SendFailure::TRANSIENT],
    'ses-v2 sending paused' => [Provider::SES_V2, 'SendingPausedException', SendFailure::PERMANENT],
    'ses-v2 too many requests' => [Provider::SES_V2, 'TooManyRequestsException', SendFailure::TRANSIENT],
])->skip(fn (): bool => ! class_exists(AwsException::class), 'aws/aws-sdk-php is not installed');
