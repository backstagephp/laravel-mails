<?php

use Backstage\Mails\Laravel\Models\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function recipientRows(Mail $mail): array
{
    return DB::table('mail_recipients')
        ->where('mail_id', $mail->getKey())
        ->orderBy('email')
        ->get(['email', 'name', 'domain'])
        ->map(fn ($row) => (array) $row)
        ->all();
}

it('stores the recipients of a new mail lowercased and deduplicated', function () {
    $mail = Mail::factory()->create([
        'to' => ['Mark@UX.nl' => 'Mark van Eijk'],
        'cc' => ['jane@example.org' => null, 'mark@ux.nl' => 'Duplicate'],
        'bcc' => ['audit@example.org' => 'Audit'],
    ]);

    expect(recipientRows($mail))->toBe([
        ['email' => 'audit@example.org', 'name' => 'audit', 'domain' => 'example.org'],
        ['email' => 'jane@example.org', 'name' => null, 'domain' => 'example.org'],
        ['email' => 'mark@ux.nl', 'name' => 'mark van eijk', 'domain' => 'ux.nl'],
    ]);
});

it('replaces the recipients when the addresses change', function () {
    $mail = Mail::factory()->create(['to' => ['old@example.com' => null]]);

    $mail->update(['to' => ['new@example.com' => null]]);

    expect(recipientRows($mail))->toBe([
        ['email' => 'new@example.com', 'name' => null, 'domain' => 'example.com'],
    ]);
});

it('leaves the recipients alone when other attributes change', function () {
    $mail = Mail::factory()->create(['to' => ['a@example.com' => null]]);
    $id = DB::table('mail_recipients')->where('mail_id', $mail->getKey())->value('id');

    $mail->update(['delivered_at' => now()]);

    expect(DB::table('mail_recipients')->where('mail_id', $mail->getKey())->value('id'))->toBe($id);
});

it('backfills the recipients of existing mails when migrating', function () {
    Schema::drop('mail_recipients');

    $id = DB::table('mails')->insertGetId([
        'mailer' => 'smtp',
        'subject' => 'Existing',
        'to' => json_encode(['First@Example.com' => 'First']),
        'cc' => json_encode(['second@example.com' => null]),
        'bcc' => null,
    ]);

    (include __DIR__.'/../database/migrations/9_create_mail_recipients_table.php.stub')->up();

    expect(recipientRows(Mail::find($id)))->toBe([
        ['email' => 'first@example.com', 'name' => 'first', 'domain' => 'example.com'],
        ['email' => 'second@example.com', 'name' => null, 'domain' => 'example.com'],
    ]);
});
