<?php

use Backstage\Mails\Enums\EventType;
use Backstage\Mails\Models\Mail as MailModel;
use Backstage\Mails\Models\MailEvent;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

it('can receive incoming delivery webhook from resend', function () {
    Mail::send([], [], function (Message $message) {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->cc('az1ru@basementdevs.cc')
            ->bcc('dev_vidal@basementdevs.cc')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => 'resend']), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid
                ]
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

it('can receive incoming hard bounce webhook from resend', function () {
    Mail::send([], [], function (Message $message) {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->cc('az1ru@basementdevs.cc')
            ->bcc('dev_vidal@basementdevs.cc')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => 'resend']), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'from' => 'local@computer.nl',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid
                ]
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

it('can receive incoming soft bounce webhook from resend', function () {
    Mail::send([], [], function (Message $message) {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->cc('az1ru@basementdevs.cc')
            ->bcc('dev_vidal@basementdevs.cc')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => 'resend']), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid
                ]
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

it('can receive incoming complaint webhook from resend', function () {
    Mail::send([], [], function (Message $message) {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->cc('az1ru@basementdevs.cc')
            ->bcc('dev_vidal@basementdevs.cc')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => 'resend']), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid
                ]
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

it('can receive incoming open webhook from resend', function () {
    Mail::send([], [], function (Message $message) {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->cc('az1ru@basementdevs.cc')
            ->bcc('dev_vidal@basementdevs.cc')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => 'resend']), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'from' => 'local@computer.nl',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid
                ]
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

it('can receive incoming click webhook from resend', function () {
    Mail::send([], [], function (Message $message) {
        $message->to('hey@danielhe4rt.dev')
            ->from('local@computer.nl')
            ->cc('az1ru@basementdevs.cc')
            ->bcc('dev_vidal@basementdevs.cc')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    post(URL::signedRoute('mails.webhook', ['provider' => 'resend']), [
        'created_at' => '2023-05-19T22:09:32Z',
        'data' => [
            'created_at' => '2025-01-09 14:17:29.059104+00',
            'email_id' => 'dummy-id',
            'from' => 'local@computer.nl',
            'subject' => 'Test',
            'headers' => [
                [
                    'name' => config('mails.headers.uuid'),
                    'value' => $mail->uuid
                ]
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
