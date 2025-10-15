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

it('can receive incoming delivery webhook from mailgun', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::MAILGUN]), [
        'event-data' => [
            'event' => 'delivered',
            'delivery-status' => [
                'message' => 'Ok: queued as 9434A204B8',
                'mx-host' => 'mail.mailkit.eu',
                'tls' => true,
                'code' => 250,
                'mx-ip' => '185.136.200.19',
            ],
            'envelope' => [
                'sending-ip' => '185.136.201.254',
                'sender' => 'bounce-501bac24-a9c2-11ec-aa58-03210c12f2eb@ov.emaildemos.com',
                'targets' => 'johndoe@mailkit.com',
                'transport' => 'smtp',
            ],
            'user-variables' => [
                'url' => [
                    'link' => 'https://example.com',
                    'title' => 'Omnivery',
                ],
                'fname' => 'John',
            ],
            'message' => [
                'size' => 6152,
                'headers' => [
                    'from' => '"Friendly Sender" <sender@emaildemos.com>',
                    'subject' => 'Test message subject',
                    'message-id' => '501bac24-a9c2-11ec-aa58-03210c12f2eb',
                    'date' => 'Tue, 22 Mar 2022 09:27:37 +0000',
                    'to' => 'johndoe@mailkit.com',
                ],
            ],
            'id' => 'OTk6MTA1MDI6ZGVsaXZlcmVkOjE2NDc5NDEyNTg=',
            'recipient' => 'johndoe@mailkit.com',
            'recipient-domain' => 'mailkit.com',
            'tags' => [
                'Test',
            ],
            'timestamp' => 1647941257.72485,
        ],
        'signature' => [
            'timestamp' => 1647941258,
            'signature' => 'secrethmacsignature',
            'token' => 'eventtoken',
        ],
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::DELIVERED->value,
    ]);
});

it('can receive incoming accept webhook from mailgun', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::MAILGUN]), [
        'signature' => [
            'timestamp' => 1649408311,
            'token' => 'eventtoken',
            'signature' => 'secrethmacsignature',
        ],
        'event-data' => [
            'event' => 'accepted',
            'timestamp' => 1649408305,
            'id' => 'OTk6MTA1MDI6YWNjZXB0ZWQ6NTYyNTQ4NzY3',
            'recipient' => 'test@omnivery.com',
            'recipient-domain' => 'omnivery.com',
            'campaigns' => [],
            'tags' => ['accepted'],
            'user-variables' => [
                config('mails.headers.uuid') => $mail?->uuid,
            ],
            'flags' => [
                'is-system-test' => false,
                'is-test-mode' => false,
            ],
            'envelope' => [
                'sending-ip' => '123.123.123.123',
                'sender' => 'sender@omnivery.dev',
                'targets' => 'test@omnivery.com',
                'transport' => 'smtp',
            ],
            'message' => [
                'headers' => [
                    'message-id' => '6d261932-b677-11ec-aa58-03210c12f2eb',
                    'subject' => 'Production test',
                    'from' => '"Friendly Sender" <sender@omnivery.dev>',
                    'to' => 'test@omnivery.com',
                    'date' => 'Thu, 7 Apr 2022 13:34:17 +0000',
                ],
                'size' => 5637,
            ],
        ],
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::ACCEPTED->value,
    ]);
});

it('can receive incoming hard bounce webhook from mailgun', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::MAILGUN]), [
        'event-data' => [
            'event' => 'failed',
            'severity' => 'permanent',
            'envelope' => [
                'sender' => 'bounce-d9bee8ac-b0e7-11ec-8086-57d93b186f66@notify.omnivery.com',
                'sending-ip' => '185.136.201.130',
                'targets' => 'nosuchemail@omnivery.com',
                'transport' => 'smtp',
            ],
            'recipient' => 'nosuchemail@omnivery.com',
            'message' => [
                'size' => 5597,
                'headers' => [
                    'message-id' => 'd9bee8ac-b0e7-11ec-8086-57d93b186f66',
                    'subject' => 'Test message subject',
                    'date' => 'Thu, 31 Mar 2022 11:43:57 +0000',
                    'to' => 'nosuchemail@omnivery.com',
                    'from' => '"Friendly Sender" <sender@emaildemos.com>',
                ],
            ],
            'delivery-status' => [
                'code' => 550,
                'bounce-class' => 'bad-mailbox',
                'description' => '550 5.1.1 <nosuchemail@omnivery.com>: Recipient address rejected: User unknown in virtual mailbox table',
                'mx-host' => 'mail.mailkit.eu',
                'tls' => true,
                'mx-ip' => '185.136.200.19',
                'message' => '<nosuchemail@omnivery.com>: Recipient address rejected: User unknown in virtual mailbox table',
            ],
            'id' => 'MTozOmhhcmRib3VuY2U6MTY0ODcyNzAzOQ==',
            'timestamp' => 1648727038.22387,
            'recipient-domain' => 'omnivery.com',
        ],
        'signature' => [
            'signature' => 'secrethmacsignature',
            'timestamp' => 1648727039,
            'token' => 'eventtoken',
        ],
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::HARD_BOUNCED->value,
    ]);
});

it('can receive incoming soft bounce webhook from mailgun', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::MAILGUN]), [
        'event-data' => [
            'event' => 'failed',
            'severity' => 'temporary',
            'envelope' => [
                'sender' => 'bounce-d9bee8ac-b0e7-11ec-8086-57d93b186f66@notify.omnivery.com',
                'sending-ip' => '185.136.201.130',
                'targets' => 'nosuchemail@omnivery.com',
                'transport' => 'smtp',
            ],
            'recipient' => 'nosuchemail@omnivery.com',
            'message' => [
                'size' => 5597,
                'headers' => [
                    'message-id' => 'd9bee8ac-b0e7-11ec-8086-57d93b186f66',
                    'subject' => 'Test message subject',
                    'date' => 'Thu, 31 Mar 2022 11:43:57 +0000',
                    'to' => 'nosuchemail@omnivery.com',
                    'from' => '"Friendly Sender" <sender@emaildemos.com>',
                ],
            ],
            'delivery-status' => [
                'code' => 550,
                'bounce-class' => 'bad-mailbox',
                'description' => '550 5.1.1 <nosuchemail@omnivery.com>: Recipient address rejected: User unknown in virtual mailbox table',
                'mx-host' => 'mail.mailkit.eu',
                'tls' => true,
                'mx-ip' => '185.136.200.19',
                'message' => '<nosuchemail@omnivery.com>: Recipient address rejected: User unknown in virtual mailbox table',
            ],
            'id' => 'MTozOmhhcmRib3VuY2U6MTY0ODcyNzAzOQ==',
            'timestamp' => 1648727038.22387,
            'recipient-domain' => 'omnivery.com',
        ],
        'signature' => [
            'signature' => 'secrethmacsignature',
            'timestamp' => 1648727039,
            'token' => 'eventtoken',
        ],
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::SOFT_BOUNCED->value,
    ]);
});

it('can receive incoming complaint webhook from mailgun', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::MAILGUN]), [
        'signature' => [
            'timestamp' => 1649408311,
            'token' => 'eventtoken',
            'signature' => 'secrethmacsignature',
        ],
        'event-data' => [
            'event' => 'complained',
            'timestamp' => 1649408305,
            'id' => 'OTk6MTA1MDI6Y29tcGxhaW5lZDo1NjI1NDg3Njc=',
            'recipient' => 'test@omnivery.com',
            'recipient-domain' => 'omnivery.com',
            'campaigns' => [],
            'tags' => ['complaint', 'feedback'],
            'user-variables' => [
                config('mails.headers.uuid') => $mail?->uuid,
            ],
            'flags' => [
                'is-system-test' => false,
                'is-test-mode' => false,
            ],
            'complaint' => [
                'complained-at' => 'Thu, 7 Apr 2022 13:34:17 +0000',
                'feedback-id' => 'feedback-id-12345',
                'user-agent' => 'Feedback Loop Processor',
            ],
            'message' => [
                'headers' => [
                    'message-id' => '6d261932-b677-11ec-aa58-03210c12f2eb',
                    'subject' => 'Production test',
                    'from' => '"Friendly Sender" <sender@omnivery.dev>',
                    'to' => 'test@omnivery.com',
                    'date' => 'Thu, 7 Apr 2022 13:34:17 +0000',
                ],
                'size' => 5637,
            ],
        ],
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::COMPLAINED->value,
    ]);
});

it('can receive incoming open webhook from mailgun', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::MAILGUN]), [
        'signature' => [
            'signature' => 'secrethmacsignature',
            'token' => 'eventtoken',
            'timestamp' => 1649408311,
        ],
        'event-data' => [
            'recipient-domain' => 'omnivery.com',
            'timestamp' => 1649408305,
            'envelope' => [
                'targets' => 'test@omnivery.com',
            ],
            'message' => [
                'headers' => [
                    'subject' => 'Production test',
                    'to' => 'test@omnivery.com',
                    'message-id' => '6d261932-b677-11ec-aa58-03210c12f2eb',
                    'from' => '"Friendly Sender" <sender@omnivery.dev>',
                    'date' => 'Thu, 7 Apr 2022 13:34:17 +0000',
                ],
            ],
            'client-info' => [
                'suspected-bot' => false,
                'device-type' => 'Personal computer',
                'client-name' => 'Thunderbird',
                'client-type' => 'Email client',
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Thunderbird/91.7.0',
                'client-os' => 'Windows 10',
            ],
            'ip' => '123.123.123.123',
            'recipient' => 'test@omnivery.com',
            'id' => 'OTk6MTA1MDI6b3BlbmVkOjE2NDk0MDgzMTE=',
            'event' => 'opened',
            'geolocation' => [
                'country_code' => 'ES',
                'continent_name' => 'Europe',
                'country_name' => 'Spain',
                'continent_code' => 'EU',
                'city' => 'Puerto de la Omnivery',
            ],
        ],
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::OPENED->value,
    ]);
});

it('can receive incoming click webhook from mailgun', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::MAILGUN]), [
        'signature' => [
            'timestamp' => 1649408311,
            'token' => 'eventtoken',
            'signature' => 'secrethmacsignature',
        ],
        'event-data' => [
            'event' => 'clicked',
            'timestamp' => 1649408305,
            'id' => 'OTk6MTA1MDI6Y2xpY2tlZDo1NjI1NDg3Njc=',
            'recipient' => 'test@omnivery.com',
            'recipient-domain' => 'omnivery.com',
            'campaigns' => [],
            'user-variables' => [
                config('mails.headers.uuid') => $mail?->uuid,
            ],
            'flags' => [
                'is-system-test' => false,
                'is-test-mode' => false,
            ],
            'ip' => '123.123.123.123',
            'geolocation' => [
                'country' => 'Spain',
                'region' => 'ES',
                'city' => 'Puerto de la Omnivery',
            ],
            'url' => 'https://example.com',
            'client-info' => [
                'client-name' => 'Chrome',
                'client-type' => 'browser',
                'device-type' => 'desktop',
                'client-os' => 'Windows',
            ],
            'message' => [
                'headers' => [
                    'message-id' => '6d261932-b677-11ec-aa58-03210c12f2eb',
                    'subject' => 'Production test',
                    'from' => '"Friendly Sender" <sender@omnivery.dev>',
                    'to' => 'test@omnivery.com',
                    'date' => 'Thu, 7 Apr 2022 13:34:17 +0000',
                ],
                'size' => 5637,
            ],
        ],
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::CLICKED->value,
        'link' => 'https://example.com',
    ]);
});

it('can receive incoming unsubscribe webhook from mailgun', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::MAILGUN]), [
        'signature' => [
            'timestamp' => 1649408311,
            'token' => 'eventtoken',
            'signature' => 'secrethmacsignature',
        ],
        'event-data' => [
            'event' => 'unsubscribed',
            'timestamp' => 1649408305,
            'id' => 'OTk6MTA1MDI6dW5zdWJzY3JpYmVkOjU2MjU0ODc2Nw==',
            'recipient' => 'test@omnivery.com',
            'recipient-domain' => 'omnivery.com',
            'campaigns' => [],
            'tags' => ['unsubscribed'],
            'user-variables' => [
                config('mails.headers.uuid') => $mail?->uuid,
            ],
            'flags' => [
                'is-system-test' => false,
                'is-test-mode' => false,
            ],
            'unsubscribe' => [
                'mailing-list' => 'newsletter@omnivery.com',
                'ip' => '123.123.123.123',
            ],
            'message' => [
                'headers' => [
                    'message-id' => '6d261932-b677-11ec-aa58-03210c12f2eb',
                    'subject' => 'Production test',
                    'from' => '"Friendly Sender" <sender@omnivery.dev>',
                    'to' => 'test@omnivery.com',
                    'date' => 'Thu, 7 Apr 2022 13:34:17 +0000',
                ],
                'size' => 5637,
            ],
        ],
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::UNSUBSCRIBED->value,
    ]);
});
