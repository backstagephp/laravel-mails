<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(config('mails.database.tables.mails'), function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable()->index();
            $table->string('mail_class')->nullable()->index();
            $table->string('subject')->nullable();
            $table->json('from')->nullable();
            $table->json('reply_to')->nullable();
            $table->json('to')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->text('html')->nullable();
            $table->text('text')->nullable();
            $table->unsignedBigInteger('opens')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('resent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamp('soft_bounced_at')->nullable();
            $table->timestamp('hard_bounced_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }
};
