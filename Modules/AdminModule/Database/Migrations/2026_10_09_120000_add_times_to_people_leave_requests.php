<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('people_leave_requests')) {
            return;
        }

        Schema::table('people_leave_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('people_leave_requests', 'from_time')) {
                $table->string('from_time', 5)->nullable()->after('hours');
            }
            if (! Schema::hasColumn('people_leave_requests', 'to_time')) {
                $table->string('to_time', 5)->nullable()->after('from_time');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('people_leave_requests')) {
            return;
        }

        Schema::table('people_leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('people_leave_requests', 'to_time')) {
                $table->dropColumn('to_time');
            }
            if (Schema::hasColumn('people_leave_requests', 'from_time')) {
                $table->dropColumn('from_time');
            }
        });
    }
};
