<?php

use Backstage\Mails\Enums\EventType;
use Backstage\Mails\Enums\Provider;
use Backstage\Mails\Models\Mail as MailModel;
use Backstage\Mails\Models\MailEvent;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

it('can receive incoming delivery webhook from amazon ses', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('mark@vormkracht10.nl')
            ->from('local@computer.nl')
            ->cc('cc@vk10.nl')
            ->bcc('bcc@vk10.nl')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    $sesEvent = [
        'eventType' => 'Delivery',
        'mail' => [
            'timestamp' => '2016-10-19T23:20:52.240Z',
            'source' => 'sender@example.com',
            'messageId' => 'EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000',
            'destination' => ['recipient@example.com'],
            'headersTruncated' => false,
            'headers' => [
                ['name' => 'From', 'value' => 'sender@example.com'],
                ['name' => 'To', 'value' => 'recipient@example.com'],
                ['name' => 'Subject', 'value' => 'Message sent from Amazon SES'],
                ['name' => config('mails.headers.uuid'), 'value' => $mail?->uuid],
            ],
        ],
        'delivery' => [
            'timestamp' => '2016-10-19T23:21:04.133Z',
            'processingTimeMillis' => 11893,
            'recipients' => ['recipient@example.com'],
            'smtpResponse' => '250 2.6.0 Message received',
            'remoteMtaIp' => '123.456.789.012',
            'reportingMTA' => 'mta.example.com',
        ],
    ];

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::SES]), [
        'Type' => 'Notification',
        'Message' => json_encode($sesEvent),
        'Timestamp' => '2016-10-19T23:21:04.133Z',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::DELIVERED->value,
    ]);
});

it('can receive incoming hard bounce webhook from amazon ses', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('mark@vormkracht10.nl')
            ->from('local@computer.nl')
            ->cc('cc@vk10.nl')
            ->bcc('bcc@vk10.nl')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    $sesEvent = [
        'eventType' => 'Bounce',
        'mail' => [
            'timestamp' => '2016-10-19T23:20:52.240Z',
            'source' => 'sender@example.com',
            'messageId' => 'EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000',
            'destination' => ['recipient@example.com'],
            'headersTruncated' => false,
            'headers' => [
                ['name' => 'From', 'value' => 'sender@example.com'],
                ['name' => 'To', 'value' => 'recipient@example.com'],
                ['name' => config('mails.headers.uuid'), 'value' => $mail?->uuid],
            ],
        ],
        'bounce' => [
            'bounceType' => 'Permanent',
            'bounceSubType' => 'General',
            'bouncedRecipients' => [
                [
                    'emailAddress' => 'recipient@example.com',
                    'action' => 'failed',
                    'status' => '5.1.1',
                    'diagnosticCode' => 'smtp; 550 5.1.1 user unknown',
                ],
            ],
            'timestamp' => '2016-10-19T23:21:04.133Z',
            'feedbackId' => 'EXAMPLE-feedback-id',
            'reportingMTA' => 'dsn; mta.example.com',
        ],
    ];

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::SES]), [
        'Type' => 'Notification',
        'Message' => json_encode($sesEvent),
        'Timestamp' => '2016-10-19T23:21:04.133Z',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::HARD_BOUNCED->value,
    ]);
});

it('can receive incoming soft bounce webhook from amazon ses', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('mark@vormkracht10.nl')
            ->from('local@computer.nl')
            ->cc('cc@vk10.nl')
            ->bcc('bcc@vk10.nl')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    $sesEvent = [
        'eventType' => 'Bounce',
        'mail' => [
            'timestamp' => '2016-10-19T23:20:52.240Z',
            'source' => 'sender@example.com',
            'messageId' => 'EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000',
            'destination' => ['recipient@example.com'],
            'headersTruncated' => false,
            'headers' => [
                ['name' => 'From', 'value' => 'sender@example.com'],
                ['name' => 'To', 'value' => 'recipient@example.com'],
                ['name' => config('mails.headers.uuid'), 'value' => $mail?->uuid],
            ],
        ],
        'bounce' => [
            'bounceType' => 'Temporary',
            'bounceSubType' => 'General',
            'bouncedRecipients' => [
                [
                    'emailAddress' => 'recipient@example.com',
                    'action' => 'failed',
                    'status' => '4.0.0',
                    'diagnosticCode' => 'smtp; 450 4.0.0 try again later',
                ],
            ],
            'timestamp' => '2016-10-19T23:21:04.133Z',
            'feedbackId' => 'EXAMPLE-feedback-id',
            'reportingMTA' => 'dsn; mta.example.com',
        ],
    ];

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::SES]), [
        'Type' => 'Notification',
        'Message' => json_encode($sesEvent),
        'Timestamp' => '2016-10-19T23:21:04.133Z',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::SOFT_BOUNCED->value,
    ]);
});

it('can receive incoming complaint webhook from amazon ses', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('mark@vormkracht10.nl')
            ->from('local@computer.nl')
            ->cc('cc@vk10.nl')
            ->bcc('bcc@vk10.nl')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    $sesEvent = [
        'eventType' => 'Complaint',
        'mail' => [
            'timestamp' => '2016-10-19T23:20:52.240Z',
            'source' => 'sender@example.com',
            'messageId' => 'EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000',
            'destination' => ['recipient@example.com'],
            'headersTruncated' => false,
            'headers' => [
                ['name' => 'From', 'value' => 'sender@example.com'],
                ['name' => 'To', 'value' => 'recipient@example.com'],
                ['name' => config('mails.headers.uuid'), 'value' => $mail?->uuid],
            ],
        ],
        'complaint' => [
            'complainedRecipients' => [
                ['emailAddress' => 'recipient@example.com'],
            ],
            'timestamp' => '2016-10-19T23:21:04.133Z',
            'feedbackId' => 'EXAMPLE-feedback-id',
            'userAgent' => 'Amazon SES Mailbox Simulator',
            'complaintFeedbackType' => 'abuse',
            'arrivalDate' => '2016-10-19T23:20:52.240Z',
        ],
    ];

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::SES]), [
        'Type' => 'Notification',
        'Message' => json_encode($sesEvent),
        'Timestamp' => '2016-10-19T23:21:04.133Z',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::COMPLAINED->value,
    ]);
});

it('can receive incoming open webhook from amazon ses', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('mark@vormkracht10.nl')
            ->from('local@computer.nl')
            ->cc('cc@vk10.nl')
            ->bcc('bcc@vk10.nl')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    $sesEvent = [
        'eventType' => 'Open',
        'mail' => [
            'timestamp' => '2016-10-19T23:20:52.240Z',
            'source' => 'sender@example.com',
            'messageId' => 'EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000',
            'destination' => ['recipient@example.com'],
            'headersTruncated' => false,
            'headers' => [
                ['name' => 'From', 'value' => 'sender@example.com'],
                ['name' => 'To', 'value' => 'recipient@example.com'],
                ['name' => config('mails.headers.uuid'), 'value' => $mail?->uuid],
            ],
        ],
        'open' => [
            'ipAddress' => '192.0.2.1',
            'timestamp' => '2016-10-19T23:25:00.000Z',
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ],
    ];

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::SES]), [
        'Type' => 'Notification',
        'Message' => json_encode($sesEvent),
        'Timestamp' => '2016-10-19T23:25:00.000Z',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::OPENED->value,
    ]);
});

it('can receive incoming click webhook from amazon ses', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('mark@vormkracht10.nl')
            ->from('local@computer.nl')
            ->cc('cc@vk10.nl')
            ->bcc('bcc@vk10.nl')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    $sesEvent = [
        'eventType' => 'Click',
        'mail' => [
            'timestamp' => '2016-10-19T23:20:52.240Z',
            'source' => 'sender@example.com',
            'messageId' => 'EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000',
            'destination' => ['recipient@example.com'],
            'headersTruncated' => false,
            'headers' => [
                ['name' => 'From', 'value' => 'sender@example.com'],
                ['name' => 'To', 'value' => 'recipient@example.com'],
                ['name' => config('mails.headers.uuid'), 'value' => $mail?->uuid],
            ],
        ],
        'click' => [
            'ipAddress' => '192.0.2.1',
            'timestamp' => '2016-10-19T23:25:00.000Z',
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'link' => 'https://example.com/tracking-link',
            'linkTags' => ['campaign-1'],
        ],
    ];

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::SES]), [
        'Type' => 'Notification',
        'Message' => json_encode($sesEvent),
        'Timestamp' => '2016-10-19T23:25:00.000Z',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::CLICKED->value,
        'link' => 'https://example.com/tracking-link',
    ]);
});

it('can receive incoming accepted webhook from amazon ses', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('mark@vormkracht10.nl')
            ->from('local@computer.nl')
            ->cc('cc@vk10.nl')
            ->bcc('bcc@vk10.nl')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    $sesEvent = [
        'eventType' => 'Send',
        'mail' => [
            'timestamp' => '2016-10-19T23:20:52.240Z',
            'source' => 'sender@example.com',
            'messageId' => 'EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000',
            'destination' => ['recipient@example.com'],
            'headersTruncated' => false,
            'headers' => [
                ['name' => 'From', 'value' => 'sender@example.com'],
                ['name' => 'To', 'value' => 'recipient@example.com'],
                ['name' => config('mails.headers.uuid'), 'value' => $mail?->uuid],
            ],
        ],
        'send' => [],
    ];

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::SES]), [
        'Type' => 'Notification',
        'Message' => json_encode($sesEvent),
        'Timestamp' => '2016-10-19T23:20:52.240Z',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::ACCEPTED->value,
    ]);
});
