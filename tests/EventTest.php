<?php

use Backstage\Mails\Laravel\Events\MailEventLogged;
use Backstage\Mails\Laravel\Events\MailHardBounced;
use Backstage\Mails\Laravel\Events\MailSent;
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

it('dispatches the sent event once a logged mail gets its sent_at', function (): void {
    Event::fake([MailSent::class]);

    $mail = Mail::factory()->create(['sent_at' => null]);

    $mail->update(['subject' => 'Still not sent']);

    Event::assertNotDispatched(MailSent::class);

    $mail->update(['sent_at' => now()]);
    $mail->update(['sent_at' => now()->addMinute()]);

    Event::assertDispatchedTimes(MailSent::class, 1);
    Event::assertDispatched(MailSent::class, fn (MailSent $event) => $event->mail->is($mail));
});
