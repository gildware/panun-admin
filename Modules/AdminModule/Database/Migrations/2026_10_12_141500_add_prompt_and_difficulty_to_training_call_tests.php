<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_call_tests', function (Blueprint $table) {
            $table->text('prompt')->nullable()->after('brief');
            $table->string('difficulty', 20)->default('standard')->after('pass_mark');
        });
    }

    public function down(): void
    {
        Schema::table('training_call_tests', function (Blueprint $table) {
            $table->dropColumn(['prompt', 'difficulty']);
        });
    }
};
