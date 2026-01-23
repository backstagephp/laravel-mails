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

it('can receive incoming delivery webhook from postmark', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::POSTMARK]), [
        'DeliveredAt' => '2023-05-19T22:09:32Z',
        'Details' => 'Test delivery webhook details',
        'MessageID' => '00000000-0000-0000-0000-000000000000',
        'MessageStream' => 'outbound',
        'Metadata' => [
            config('mails.headers.uuid') => $mail?->uuid,
        ],
        'Recipient' => 'mark@vormkracht10.nl',
        'RecordType' => 'Delivery',
        'ServerID' => 23,
        'Tag' => 'welcome-email',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::DELIVERED->value,
    ]);
});

it('can receive incoming hard bounce webhook from postmark', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::POSTMARK]), [
        'BouncedAt' => '2023-05-21T02:51:39Z',
        'CanActivate' => true,
        'Content' => 'Test content',
        'Description' => 'The server was unable to deliver your message (ex => unknown user, mailbox not found).',
        'Details' => 'Test bounce details',
        'DumpAvailable' => true,
        'Email' => 'john@example.com',
        'From' => 'sender@example.com',
        'ID' => 42,
        'Inactive' => true,
        'MessageID' => '00000000-0000-0000-0000-000000000000',
        'MessageStream' => 'outbound',
        'Metadata' => [
            config('mails.headers.uuid') => $mail?->uuid,
        ],
        'Name' => 'Hard bounce',
        'RecordType' => 'Bounce',
        'ServerID' => 1234,
        'Subject' => 'Test subject',
        'Tag' => 'Test',
        'Type' => 'HardBounce',
        'TypeCode' => 1,
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::HARD_BOUNCED->value,
    ]);
});

it('can receive incoming soft bounce webhook from postmark', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::POSTMARK]), [
        'BouncedAt' => '2023-05-21T02:51:39Z',
        'CanActivate' => true,
        'Content' => 'Test content',
        'Description' => 'The server was unable to deliver your message (ex => unknown user, mailbox not found).',
        'Details' => 'Test bounce details',
        'DumpAvailable' => true,
        'Email' => 'john@example.com',
        'From' => 'sender@example.com',
        'ID' => 42,
        'Inactive' => true,
        'MessageID' => '00000000-0000-0000-0000-000000000000',
        'MessageStream' => 'outbound',
        'Metadata' => [
            config('mails.headers.uuid') => $mail?->uuid,
        ],
        'Name' => 'Soft bounce',
        'RecordType' => 'Bounce',
        'ServerID' => 1234,
        'Subject' => 'Test subject',
        'Tag' => 'Test',
        'Type' => 'SoftBounce',
        'TypeCode' => 1,
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::SOFT_BOUNCED->value,
    ]);
});

it('can receive incoming complaint webhook from postmark', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::POSTMARK]), [
        'BouncedAt' => '2023-05-21T02:51:39Z',
        'CanActivate' => false,
        'Content' => 'Test content',
        'Description' => 'The subscriber explicitly marked this message as spam.',
        'Details' => 'Test spam complaint details',
        'DumpAvailable' => true,
        'Email' => 'john@example.com',
        'From' => 'sender@example.com',
        'ID' => 42,
        'Inactive' => true,
        'MessageID' => '00000000-0000-0000-0000-000000000000',
        'MessageStream' => 'outbound',
        'Metadata' => [
            config('mails.headers.uuid') => $mail?->uuid,
        ],
        'Name' => 'Spam complaint',
        'RecordType' => 'SpamComplaint',
        'ServerID' => 1234,
        'Subject' => 'Test subject',
        'Tag' => 'Test',
        'Type' => 'SpamComplaint',
        'TypeCode' => 100001,
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::COMPLAINED->value,
    ]);
});

it('can receive incoming open webhook from postmark', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::POSTMARK]), [
        'Client' => [
            'Company' => 'Google',
            'Family' => 'Chrome',
            'Name' => 'Chrome 35.0.1916.153',
        ],
        'FirstOpen' => true,
        'Geo' => [
            'City' => 'Novi Sad',
            'Coords' => '45.2517,19.8369',
            'Country' => 'Serbia',
            'CountryISOCode' => 'RS',
            'IP' => '188.2.95.4',
            'Region' => 'Autonomna Pokrajina Vojvodina',
            'RegionISOCode' => 'VO',
            'Zip' => '21000',
        ],
        'MessageID' => '00000000-0000-0000-0000-000000000000',
        'MessageStream' => 'outbound',
        'Metadata' => [
            config('mails.headers.uuid') => $mail?->uuid,
        ],
        'OS' => [
            'Name' => 'OS X 10.7 Lion',
            'Company' => 'Apple Computer, Inc.',
            'Family' => 'OS X 10',
        ],
        'Platform' => 'WebMail',
        'ReadSeconds' => 5,
        'ReceivedAt' => '2023-05-21T02:51:39Z',
        'Recipient' => 'john@example.com',
        'RecordType' => 'Open',
        'Tag' => 'welcome-email',
        'UserAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.153 Safari/537.36',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::OPENED->value,
    ]);
});

it('can receive incoming click webhook from postmark', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::POSTMARK]), [
        'Client' => [
            'Company' => 'Google',
            'Family' => 'Chrome',
            'Name' => 'Chrome 35.0.1916.153',
        ],
        'ClickLocation' => 'HTML',
        'Geo' => [
            'City' => 'Novi Sad',
            'Coords' => '45.2517,19.8369',
            'Country' => 'Serbia',
            'CountryISOCode' => 'RS',
            'IP' => '188.2.95.4',
            'Region' => 'Autonomna Pokrajina Vojvodina',
            'RegionISOCode' => 'VO',
            'Zip' => '21000',
        ],
        'MessageID' => '00000000-0000-0000-0000-000000000000',
        'MessageStream' => 'outbound',
        'Metadata' => [
            config('mails.headers.uuid') => $mail?->uuid,
        ],
        'OriginalLink' => 'https://example.com',
        'OS' => [
            'Company' => 'Apple Computer, Inc.',
            'Family' => 'OS X 10',
            'Name' => 'OS X 10.7 Lion',
        ],
        'Platform' => 'Desktop',
        'ReceivedAt' => '2023-05-21T02:51:39Z',
        'Recipient' => 'john@example.com',
        'RecordType' => 'Click',
        'Tag' => 'welcome-email',
        'UserAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.153 Safari/537.36',
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::CLICKED->value,
        'link' => 'https://example.com',
    ]);
});

it('can receive incoming subscription change webhook from postmark', function (): void {
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

    post(URL::signedRoute('mails.webhook', ['provider' => Provider::POSTMARK]), [
        'ChangedAt' => '2024-12-08T06:03:20Z',
        'Metadata' => [
            config('mails.headers.uuid') => $mail?->uuid,
        ],
        'MessageID' => '00000000-0000-0000-0000-000000000000',
        'MessageStream' => 'outbound',
        'Origin' => 'Recipient',
        'Recipient' => 'john@example.com',
        'RecordType' => 'SubscriptionChange',
        'ServerID' => 23,
        'SuppressionReason' => 'HardBounce',
        'SuppressSending' => true,
    ])->assertAccepted();

    assertDatabaseHas((new MailEvent)->getTable(), [
        'type' => EventType::UNSUBSCRIBED->value,
    ]);
});
