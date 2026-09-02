<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('task_ticket_comments', 'mentioned_user_ids')) {
            return;
        }

        Schema::table('task_ticket_comments', function (Blueprint $table) {
            $table->json('mentioned_user_ids')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('task_ticket_comments', 'mentioned_user_ids')) {
            return;
        }

        Schema::table('task_ticket_comments', function (Blueprint $table) {
            $table->dropColumn('mentioned_user_ids');
        });
    }
};
