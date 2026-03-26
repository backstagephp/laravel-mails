<?php

use Backstage\Mails\Laravel\Models\Mail as MailModel;
use Backstage\Mails\Laravel\Tests\Fixtures\TestModel;
use Backstage\Mails\Laravel\Tests\Fixtures\TestNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Schema::create('test_models', function ($table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('email')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('test_models');
});

it('sends a notification with associated models and creates mail and pivot records', function () {
    $model = TestModel::create(['name' => 'Test', 'email' => 'test@example.com']);

    $model->notify(new TestNotification([$model]));

    $mail = MailModel::latest()->first();

    expect($mail)->not->toBeNull();
    expect($mail->subject)->toBe('Test Notification');

    assertDatabaseHas('mailables', [
        'mail_id' => $mail->id,
        'mailable_type' => TestModel::class,
        'mailable_id' => $model->id,
    ]);
});

it('associates multiple models with a notification', function () {
    $modelA = TestModel::create(['name' => 'Model A', 'email' => 'a@example.com']);
    $modelB = TestModel::create(['name' => 'Model B', 'email' => 'b@example.com']);

    $modelA->notify(new TestNotification([$modelA, $modelB]));

    $mail = MailModel::latest()->first();

    expect($mail)->not->toBeNull();

    assertDatabaseHas('mailables', [
        'mail_id' => $mail->id,
        'mailable_type' => TestModel::class,
        'mailable_id' => $modelA->id,
    ]);

    assertDatabaseHas('mailables', [
        'mail_id' => $mail->id,
        'mailable_type' => TestModel::class,
        'mailable_id' => $modelB->id,
    ]);
});

it('sends a notification without associated models', function () {
    $model = TestModel::create(['name' => 'Test', 'email' => 'test@example.com']);

    $model->notify(new TestNotification);

    $mail = MailModel::latest()->first();

    expect($mail)->not->toBeNull();
    expect($mail->subject)->toBe('Test Notification');

    expect(
        DB::table('mailables')
            ->where('mail_id', $mail->id)
            ->exists()
    )->toBeFalse();
});
