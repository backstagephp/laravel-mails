<?php

use Backstage\Mails\Laravel\Models\Mail as MailModel;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    config()->set('mail.mailers.smtp', [
        'transport' => 'smtp',
    ]);
});

it('attaches uuid to smtp mails when logging is enabled', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('mark@vormkracht10.nl')
            ->from('local@computer.nl')
            ->subject('Test')
            ->text('Text');
    });

    $mail = MailModel::latest()->first();

    expect($mail)->not->toBeNull();
    expect($mail->uuid)->not->toBeNull();
});

it('sets sent_at for smtp mails', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('mark@vormkracht10.nl')
            ->from('local@computer.nl')
            ->subject('Test')
            ->text('Text');
    });

    $mail = MailModel::latest()->first();

    expect($mail)->not->toBeNull();
    expect($mail->sent_at)->not->toBeNull();
});

it('logs smtp mail with correct attributes', function (): void {
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

    expect($mail)->not->toBeNull();
    expect($mail->uuid)->not->toBeNull();
    expect($mail->sent_at)->not->toBeNull();
    expect($mail->from)->toEqual(['local@computer.nl' => null]);
    expect($mail->to)->toEqual(['mark@vormkracht10.nl' => null]);
    expect($mail->cc)->toEqual(['cc@vk10.nl' => null]);
    expect($mail->bcc)->toEqual(['bcc@vk10.nl' => null]);
    expect($mail->subject)->toBe('Test');
    expect($mail->html)->toBe('<p>HTML</p>');
    expect($mail->text)->toBe('Text');
});

it('does not attach uuid to smtp mails when logging is disabled', function (): void {
    config()->set('mails.logging.enabled', false);

    Mail::send([], [], function (Message $message): void {
        $message->to('mark@vormkracht10.nl')
            ->from('local@computer.nl')
            ->subject('Test')
            ->text('Text');
    });

    expect(MailModel::count())->toBe(0);
});
