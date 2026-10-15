<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_call_tests', function (Blueprint $table) {
            $table->json('skill_marks')->nullable()->after('skills');
            $table->string('tone', 20)->default('warm')->after('difficulty');
            $table->string('speed', 20)->default('normal')->after('tone');
            $table->string('default_language', 8)->default('en')->after('speed');
            $table->string('starter', 20)->default('caller')->after('default_language');
            $table->unsignedTinyInteger('max_minutes')->default(8)->after('starter');
        });
    }

    public function down(): void
    {
        Schema::table('training_call_tests', function (Blueprint $table) {
            $table->dropColumn(['skill_marks', 'tone', 'speed', 'default_language', 'starter', 'max_minutes']);
        });
    }
};
