<?php

use Backstage\Mails\Events\MailEventLogged;
use Backstage\Mails\Events\MailHardBounced;
use Backstage\Mails\Models\Mail;
use Illuminate\Support\Facades\Event;

it('dispaches events when an mail is logged', function (): void {
    Event::fake([
        MailEventLogged::class,
        MailHardBounced::class,
    ]);

    Mail::factory()
        ->hasEvents(1, [
            'type' => 'hard_bounced',
        ])
        ->create();

    Event::assertDispatched(MailEventLogged::class);
    Event::assertDispatched(MailHardBounced::class);
});
