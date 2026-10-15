<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_lessons', function (Blueprint $table) {
            $table->string('media_kind', 20)->nullable()->after('body');
            $table->string('media_path', 500)->nullable()->after('media_kind');
        });
    }

    public function down(): void
    {
        Schema::table('training_lessons', function (Blueprint $table) {
            $table->dropColumn(['media_kind', 'media_path']);
        });
    }
};
