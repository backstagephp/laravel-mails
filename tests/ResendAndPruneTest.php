<?php

use Backstage\Mails\Laravel\Actions\ResendMail;
use Backstage\Mails\Laravel\Jobs\ResendMailJob;
use Backstage\Mails\Laravel\Models\Mail as MailModel;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\artisan;

it('queues a resend through the action', function (): void {
    Queue::fake();

    $mail = MailModel::factory()->create();

    (new ResendMail)($mail, to: ['other@example.com']);

    Queue::assertPushed(ResendMailJob::class);
});

it('queues a resend through the command without prompting when all recipients are given', function (): void {
    Queue::fake();

    $mail = MailModel::factory()->create();

    artisan('mail:resend', [
        'uuid' => $mail->uuid,
        'to' => ['other@example.com'],
        '--cc' => ['boss@example.com'],
        '--bcc' => ['archive@example.com'],
    ])->assertSuccessful();

    Queue::assertPushed(ResendMailJob::class);
});

it('prompts for the recipients that were not given', function (): void {
    Queue::fake();

    $mail = MailModel::factory()->create();

    artisan('mail:resend', ['uuid' => $mail->uuid])
        ->expectsQuestion('What email address do you want to send the mail to?', 'other@example.com')
        ->expectsQuestion('What email address should be included in the cc?', '')
        ->expectsQuestion('What email address should be included in the bcc?', '')
        ->assertSuccessful();

    Queue::assertPushed(ResendMailJob::class);
});

it('prunes logged mails older than the configured period', function (): void {
    config()->set('mails.database.pruning.enabled', true);
    config()->set('mails.database.pruning.after', 30);

    MailModel::factory()->create(['created_at' => now()->subDays(60)]);
    $recent = MailModel::factory()->create(['created_at' => now()->subDays(2)]);

    artisan('mail:prune')->assertSuccessful();

    expect(MailModel::pluck('id')->all())->toBe([$recent->id]);
});

it('does not prune when pruning is disabled', function (): void {
    config()->set('mails.database.pruning.enabled', false);

    MailModel::factory()->create(['created_at' => now()->subDays(60)]);

    artisan('mail:prune')->assertSuccessful();

    expect(MailModel::count())->toBe(1);
});
