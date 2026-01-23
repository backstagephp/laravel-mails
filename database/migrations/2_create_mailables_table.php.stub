<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(config('mails.database.tables.polymorph'), function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(config('mails.models.mail'))
                ->constrained()
                ->cascadeOnDelete();
            $table->morphs('mailable');
        });
    }
};
