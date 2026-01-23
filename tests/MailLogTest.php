<?php

use Backstage\Mails\Models\Mail as MailModel;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\assertDatabaseHas;

it('can log sent mails', function (): void {
    Mail::send([], [], function (Message $message): void {
        $message->to('mark@vormkracht10.nl')
            ->from('local@computer.nl')
            ->cc('cc@vk10.nl')
            ->bcc('bcc@vk10.nl')
            ->subject('Test')
            ->text('Text')
            ->html('<p>HTML</p>');
    });

    assertDatabaseHas((new MailModel)->getTable(), [
        'from' => json_encode(['local@computer.nl' => null]),
        'to' => json_encode(['mark@vormkracht10.nl' => null]),
        'cc' => json_encode(['cc@vk10.nl' => null]),
        'bcc' => json_encode(['bcc@vk10.nl' => null]),
        'subject' => 'Test',
        'html' => '<p>HTML</p>',
        'text' => 'Text',
    ]);
});
