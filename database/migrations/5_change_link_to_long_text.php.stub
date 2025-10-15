<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table(config('mails.database.tables.events', 'mail_events'), function (Blueprint $table) {
            $table->longText('link')->nullable()->change();
        });
        
    }

    public function down()
    {
        Schema::table(config('mails.database.tables.events', 'mail_events'), function (Blueprint $table) {
            $table->string('link')->nullable()->change();
        });
    }
};
