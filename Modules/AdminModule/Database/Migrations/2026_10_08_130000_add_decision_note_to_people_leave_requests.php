<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('people_leave_requests') && ! Schema::hasColumn('people_leave_requests', 'decision_note')) {
            Schema::table('people_leave_requests', function (Blueprint $table) {
                $table->text('decision_note')->nullable()->after('decided_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('people_leave_requests') && Schema::hasColumn('people_leave_requests', 'decision_note')) {
            Schema::table('people_leave_requests', function (Blueprint $table) {
                $table->dropColumn('decision_note');
            });
        }
    }
};
