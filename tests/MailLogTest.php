<?php

use Backstage\Mails\Laravel\Models\Mail as MailModel;
use Backstage\Mails\Laravel\Tests\Fixtures\TaggedMailable;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\assertDatabaseHas;

it('can log sent mails', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('mark@backstagephp.com')
            ->from('local@computer.nl')
            ->cc('cc@vk10.nl')
            ->bcc('bcc@vk10.nl')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    assertDatabaseHas((new MailModel)->getTable(), [
        'from' => json_encode(['local@computer.nl' => null]),
        'to' => json_encode(['mark@backstagephp.com' => null]),
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

    Mail::to('mark@backstagephp.com')
        ->send(new TaggedMailable(['Campaign:42', 'audience:newsletter']));

    $mail = MailModel::firstWhere('subject', 'Tagged subject');

    expect($mail)->not->toBeNull()
        ->and($mail->tags)->toBe(['Campaign:42', 'audience:newsletter']);
});

it('logs an empty tag list when the envelope has no tags', function (): void {
    config()->set('mails.logging.attributes', array_merge(
        (array) config('mails.logging.attributes'),
        ['tags'],
    ));

    Mail::to('mark@backstagephp.com')->send(new TaggedMailable([]));

    $mail = MailModel::firstWhere('subject', 'Tagged subject');

    expect($mail)->not->toBeNull()
        ->and($mail->tags)->toBe([]);
});

it('does not log tags when tags is not in the configured attributes', function (): void {
    config()->set('mails.logging.attributes', ['subject', 'to']);

    Mail::to('mark@backstagephp.com')
        ->send(new TaggedMailable(['Campaign:42']));

    $mail = MailModel::firstWhere('subject', 'Tagged subject');

    expect($mail)->not->toBeNull()
        ->and($mail->tags)->toBeNull();
});
