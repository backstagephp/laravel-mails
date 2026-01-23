<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table(config('mails.database.tables.mails'), function (Blueprint $table) {
            $table->after('clicks', function (Blueprint $table) {
                $table->json('tags')->nullable();
            });
        });
    }
};
