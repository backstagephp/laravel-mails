<?php

use Backstage\Mails\Models\Mail as MailModel;
use Backstage\Mails\Tests\Fixtures\TaggedMailable;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\assertDatabaseHas;

it('can log sent mails', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('mark@ux.nl')
            ->from('local@computer.nl')
            ->cc('cc@vk10.nl')
            ->bcc('bcc@vk10.nl')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    assertDatabaseHas((new MailModel)->getTable(), [
        'from' => json_encode(['local@computer.nl' => null]),
        'to' => json_encode(['mark@ux.nl' => null]),
        'cc' => json_encode(['cc@vk10.nl' => null]),
        'bcc' => json_encode(['bcc@vk10.nl' => null]),
        'subject' => 'Test',
        'html' => '<p>HTML</p>',
        'text' => 'Text',
    ]);
});

it('logs tags from a mailable envelope', function (): void {
    config()->set('mails.logging.attributes', array_merge(
        (array) config('mails.logging.attributes'),
        ['tags'],
    ));

    Mail::to('mark@ux.nl')
        ->send(new TaggedMailable(['Campaign:42', 'audience:newsletter']));

    assertDatabaseHas((new MailModel)->getTable(), [
        'subject' => 'Tagged subject',
        'tags' => json_encode(['Campaign:42', 'audience:newsletter']),
    ]);
});

it('logs an empty tag list when the envelope has no tags', function (): void {
    config()->set('mails.logging.attributes', array_merge(
        (array) config('mails.logging.attributes'),
        ['tags'],
    ));

    Mail::to('mark@ux.nl')->send(new TaggedMailable([]));

    assertDatabaseHas((new MailModel)->getTable(), [
        'subject' => 'Tagged subject',
        'tags' => json_encode([]),
    ]);
});
