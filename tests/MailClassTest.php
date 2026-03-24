<?php

use Backstage\Mails\Models\Mail as MailModel;
use Backstage\Mails\Tests\Fixtures\TestMailable;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

it('stores mail_class as FQCN when sending a Mailable class', function (): void {
    Mail::to('recipient@example.com')->send(new TestMailable);

    $mail = MailModel::latest()->first();

    expect($mail)->not->toBeNull();
    expect($mail->mail_class)->toBe(TestMailable::class);
});

it('stores mail_class as null when sending with a closure', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('recipient@example.com')
            ->from('from@example.com')
            ->subject('Closure Mail')
            ->html('<p>HTML</p>');
    });

    $mail = MailModel::latest()->first();

    expect($mail)->not->toBeNull();
    expect($mail->mail_class)->toBeNull();
});

it('can query mails by mail_class using forMailClass scope', function (): void {
    Mail::to('recipient@example.com')->send(new TestMailable);

    Mail::send([], [], function (Message $message): void {
        $message->to('other@example.com')
            ->from('from@example.com')
            ->subject('Closure Mail')
            ->html('<p>HTML</p>');
    });

    expect(MailModel::count())->toBe(2);
    expect(MailModel::forMailClass(TestMailable::class)->count())->toBe(1);
    expect(MailModel::forMailClass(TestMailable::class)->first()->subject)->toBe('Test Mailable');
});
