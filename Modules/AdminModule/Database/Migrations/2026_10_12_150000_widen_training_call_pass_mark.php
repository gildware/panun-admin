<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_call_tests', function (Blueprint $table) {
            $table->unsignedSmallInteger('pass_mark')->default(70)->change();
        });
    }

    public function down(): void
    {
        Schema::table('training_call_tests', function (Blueprint $table) {
            $table->unsignedTinyInteger('pass_mark')->default(70)->change();
        });
    }
};
