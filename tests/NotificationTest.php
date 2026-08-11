<?php

use Backstage\Mails\Laravel\Models\Mail;
use Backstage\Mails\Laravel\Notifications\BounceNotification;
use Backstage\Mails\Laravel\Notifications\SpamComplaintNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => config([
    'mails.events.hard_bounced.notify' => [
        'mail',
    ],
    'mails.events.complained.notify' => [
        'mail',
    ],
    'mails.notifications.mail.to' => [
        'm@rkvaneijk.nl',
    ],
]));

it('will send notification on bounce', function (): void {
    Notification::fake();

    Mail::factory()
        ->hasEvents(1, [
            'type' => 'hard_bounced',
        ])
        ->create();

    Notification::assertSentTimes(
        BounceNotification::class,
        1
    );
});

it('will send notification on spam complaint', function (): void {
    Notification::fake();

    Mail::factory()
        ->hasEvents(1, [
            'type' => 'complained',
        ])
        ->create();

    Notification::assertSentTimes(
        SpamComplaintNotification::class,
        1
    );
});

it('will not notify when no channels are configured', function (): void {
    config(['mails.events.hard_bounced.notify' => []]);

    Notification::fake();

    Mail::factory()
        ->hasEvents(1, [
            'type' => 'hard_bounced',
        ])
        ->create();

    Notification::assertNothingSent();
});
