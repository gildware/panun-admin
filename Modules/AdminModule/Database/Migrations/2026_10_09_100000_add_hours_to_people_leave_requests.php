<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('people_leave_requests') && ! Schema::hasColumn('people_leave_requests', 'hours')) {
            Schema::table('people_leave_requests', function (Blueprint $table) {
                $table->decimal('hours', 4, 1)->nullable()->after('days');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('people_leave_requests') && Schema::hasColumn('people_leave_requests', 'hours')) {
            Schema::table('people_leave_requests', function (Blueprint $table) {
                $table->dropColumn('hours');
            });
        }
    }
};
