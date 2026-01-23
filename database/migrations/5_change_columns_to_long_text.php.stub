<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table(config('mails.database.tables.mails'), function (Blueprint $table) {
            $table->longText('html')->nullable()->change();
            $table->longText('text')->nullable()->change();
        });
        
    }

    public function down()
    {
        Schema::table(config('mails.database.tables.mails'), function (Blueprint $table) {
            $table->text('html')->nullable()->change();
            $table->text('text')->nullable()->change();
        });
    }
};
