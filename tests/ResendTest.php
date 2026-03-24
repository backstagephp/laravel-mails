<?php

use Backstage\Mails\Drivers\ResendDriver;
use Backstage\Mails\Enums\EventType;
use Backstage\Mails\Enums\Provider;
use Backstage\Mails\Models\Mail as MailModel;
use Backstage\Mails\Models\MailEvent;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Console\Output\BufferedOutput;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

it('can receive incoming delivery webhook from resend', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->cc('az1ru@basementdevs.cc')
            ->bcc('dev_vidal@basementdevs.cc')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND]), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid,
                ],
            ],
            'from' => 'local@computer.nl',
            'subject' => 'Test',
            'to' => ['hey@danielhe4rt.com'],
            'cc' => ['az1ru@basementdevs.cc'],
            'bcc' => ['dev_vidal@basementdevs.cc'],
        ],
        'type' => 'email.delivered',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::DELIVERED->value,
    ]);
});

it('can receive incoming sent webhook from resend', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND]), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid,
                ],
            ],
            'from' => 'local@computer.nl',
            'subject' => 'Test',
            'to' => ['hey@danielhe4rt.dev'],
        ],
        'type' => 'email.sent',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::ACCEPTED->value,
    ]);
});

it('can receive incoming hard bounce webhook from resend', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->cc('az1ru@basementdevs.cc')
            ->bcc('dev_vidal@basementdevs.cc')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND]), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'from' => 'local@computer.nl',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid,
                ],
            ],
            'subject' => 'Test',
            'to' => ['hey@danielhe4rt.com'],
            'cc' => ['az1ru@basementdevs.cc'],
            'bcc' => ['dev_vidal@basementdevs.cc'],
        ],
        'type' => 'email.bounced',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::HARD_BOUNCED->value,
    ]);
});

it('can receive incoming soft bounce webhook from resend', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->cc('az1ru@basementdevs.cc')
            ->bcc('dev_vidal@basementdevs.cc')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND]), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid,
                ],
            ],
            'from' => 'local@computer.nl',
            'subject' => 'Test',
            'to' => ['hey@danielhe4rt.com'],
            'cc' => ['az1ru@basementdevs.cc'],
            'bcc' => ['dev_vidal@basementdevs.cc'],
        ],
        'type' => 'email.delivery_delayed',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::SOFT_BOUNCED->value,
    ]);
});

it('can receive incoming complaint webhook from resend', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->cc('az1ru@basementdevs.cc')
            ->bcc('dev_vidal@basementdevs.cc')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND]), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid,
                ],
            ],
            'from' => 'local@computer.nl',
            'subject' => 'Test',
            'to' => ['hey@danielhe4rt.com'],
            'cc' => ['az1ru@basementdevs.cc'],
            'bcc' => ['dev_vidal@basementdevs.cc'],
        ],
        'type' => 'email.complained',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::COMPLAINED->value,
    ]);
});

it('can receive incoming open webhook from resend', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->cc('az1ru@basementdevs.cc')
            ->bcc('dev_vidal@basementdevs.cc')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND]), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'from' => 'local@computer.nl',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid,
                ],
            ],
            'subject' => 'Test',
            'to' => ['hey@danielhe4rt.com'],
            'cc' => ['az1ru@basementdevs.cc'],
            'bcc' => ['dev_vidal@basementdevs.cc'],
        ],
        'type' => 'email.opened',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::OPENED->value,
    ]);
});

it('can receive incoming click webhook from resend', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->cc('az1ru@basementdevs.cc')
            ->bcc('dev_vidal@basementdevs.cc')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND]), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'from' => 'local@computer.nl',
            'subject' => 'Test',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid,
                ],
            ],
            'to' => ['hey@danielhe4rt.com'],
            'cc' => ['az1ru@basementdevs.cc'],
            'bcc' => ['dev_vidal@basementdevs.cc'],
            'click' => [
                'ipAddress' => '122.115.53.11',
                'link' => 'https://resend.com',
                'timestamp' => '2024-11-24T05:00:57.163Z',
                'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.1 Safari/605.1.15',
            ],
        ],
        'type' => 'email.clicked',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::CLICKED->value,
        'link' => 'https://resend.com',
    ]);
});

it('can register webhooks via resend api', function (): void {
    config()->set('services.resend.key', 're_test_123');

    Http::fake([
        'api.resend.com/webhooks' => Http::sequence()
            ->push(['object' => 'list', 'data' => []])
            ->push([
                'object' => 'webhook',
                'id' => 'wh_test_123',
                'signing_secret' => 'whsec_test_signing_secret',
            ]),
    ]);

    $driver = new ResendDriver;

    $output = new BufferedOutput;
    $factory = new Factory($output);

    $driver->registerWebhooks($factory);

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => $request->method() === 'GET' && $request->url() === 'https://api.resend.com/webhooks');
    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && $request->url() === 'https://api.resend.com/webhooks'
        && ! empty($request['endpoint'])
        && ! empty($request['events'])
    );
});

it('skips webhook registration when resend webhook already exists', function (): void {
    config()->set('services.resend.key', 're_test_123');

    $webhookUrl = URL::signedRoute('mails.webhook', ['provider' => Provider::RESEND]);

    Http::fake([
        'api.resend.com/webhooks' => Http::response([
            'object' => 'list',
            'data' => [
                [
                    'id' => 'wh_existing_123',
                    'endpoint' => $webhookUrl,
                    'events' => ['email.delivered'],
                    'status' => 'enabled',
                ],
            ],
        ]),
    ]);

    $driver = new ResendDriver;

    $output = new BufferedOutput;
    $factory = new Factory($output);

    $driver->registerWebhooks($factory);

    Http::assertSentCount(1);
});

it('verifies svix webhook signature correctly', function (): void {
    $secret = 'whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw';
    $secretBytes = base64_decode(str_replace('whsec_', '', $secret));

    $body = json_encode(['type' => 'email.delivered', 'data' => ['email_id' => 'test']]);
    $msgId = 'msg_test_123';
    $timestamp = (string) time();
    $signedContent = "{$msgId}.{$timestamp}.{$body}";
    $signature = base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

    config()->set('services.resend.webhook_signing_secret', $secret);

    $driver = new ResendDriver;

    // Simulate a request with Svix headers
    $request = Request::create(
        '/webhooks/mails/resend',
        'POST',
        [],
        [],
        [],
        [
            'HTTP_SVIX_ID' => $msgId,
            'HTTP_SVIX_TIMESTAMP' => $timestamp,
            'HTTP_SVIX_SIGNATURE' => "v1,{$signature}",
            'CONTENT_TYPE' => 'application/json',
        ],
        $body,
    );

    app()->instance('request', $request);

    // Override runningUnitTests to actually test verification
    $this->app->detectEnvironment(fn () => 'production');

    expect($driver->verifyWebhookSignature(json_decode($body, true)))->toBeTrue();
});

it('rejects invalid svix webhook signature', function (): void {
    $secret = 'whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw';

    $body = json_encode(['type' => 'email.delivered', 'data' => ['email_id' => 'test']]);
    $msgId = 'msg_test_123';
    $timestamp = (string) time();

    config()->set('services.resend.webhook_signing_secret', $secret);

    $driver = new ResendDriver;

    $request = Request::create(
        '/webhooks/mails/resend',
        'POST',
        [],
        [],
        [],
        [
            'HTTP_SVIX_ID' => $msgId,
            'HTTP_SVIX_TIMESTAMP' => $timestamp,
            'HTTP_SVIX_SIGNATURE' => 'v1,invalidsignature',
            'CONTENT_TYPE' => 'application/json',
        ],
        $body,
    );

    app()->instance('request', $request);

    $this->app->detectEnvironment(fn () => 'production');

    expect($driver->verifyWebhookSignature(json_decode($body, true)))->toBeFalse();
});

it('rejects svix webhook with expired timestamp', function (): void {
    $secret = 'whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw';
    $secretBytes = base64_decode(str_replace('whsec_', '', $secret));

    $body = json_encode(['type' => 'email.delivered', 'data' => ['email_id' => 'test']]);
    $msgId = 'msg_test_123';
    $timestamp = (string) (time() - 600); // 10 minutes ago
    $signedContent = "{$msgId}.{$timestamp}.{$body}";
    $signature = base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

    config()->set('services.resend.webhook_signing_secret', $secret);

    $driver = new ResendDriver;

    $request = Request::create(
        '/webhooks/mails/resend',
        'POST',
        [],
        [],
        [],
        [
            'HTTP_SVIX_ID' => $msgId,
            'HTTP_SVIX_TIMESTAMP' => $timestamp,
            'HTTP_SVIX_SIGNATURE' => "v1,{$signature}",
            'CONTENT_TYPE' => 'application/json',
        ],
        $body,
    );

    app()->instance('request', $request);

    $this->app->detectEnvironment(fn () => 'production');

    expect($driver->verifyWebhookSignature(json_decode($body, true)))->toBeFalse();
});
