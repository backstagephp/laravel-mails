<?php

use Backstage\Mails\Laravel\Events\MailEventLogged;
use Backstage\Mails\Laravel\Events\MailHardBounced;
use Backstage\Mails\Laravel\Models\Mail;
use Illuminate\Support\Facades\Event;

it('dispatches events when an mail is logged', function (): void {
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
