<?php

use Backstage\Mails\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in(__DIR__);

beforeEach(function (): void {
    Mail::fake();
});
