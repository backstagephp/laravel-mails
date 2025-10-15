<?php

use Backstage\Mails\Models\Mail;
use Backstage\Mails\Notifications\BounceNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => config([
    'mails.events.bounce.notify' => [
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
        BounceNotification::class, 1
    );
});
