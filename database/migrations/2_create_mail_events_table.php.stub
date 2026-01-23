<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(config('mails.database.tables.events', 'mail_events'), function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(config('mails.models.mail'))
                ->constrained()
                ->cascadeOnDelete();
            $table->string('type');
            $table->string('ip_address')->nullable();
            $table->string('hostname')->nullable();
            $table->string('platform')->nullable();
            $table->string('os')->nullable();
            $table->string('browser')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('city')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('link')->nullable();
            $table->string('tag')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->timestamp('occurred_at')->nullable();
        });
    }
};
