<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Backstage\Mails\Tests\TestCase;

uses(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in(__DIR__);

beforeEach(function () {
    Mail::fake();
});
