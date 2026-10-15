<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_lesson_progress', function (Blueprint $table) {
            $table->timestamp('content_seen_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('training_lesson_progress', function (Blueprint $table) {
            $table->dropColumn('content_seen_at');
        });
    }
};
