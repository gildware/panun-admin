<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('people_leave_types') && ! Schema::hasColumn('people_leave_types', 'allows_future')) {
            Schema::table('people_leave_types', function (Blueprint $table) {
                $table->boolean('allows_future')->default(true)->after('tracks_balance');
            });
        }

        if (Schema::hasTable('people_leave_types') && Schema::hasColumn('people_leave_types', 'allows_future')) {
            DB::table('people_leave_types')->where(function ($query) {
                $query->where('code', 'sick')->orWhere('short_name', 'SL');
            })->update(['allows_future' => false]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('people_leave_types') && Schema::hasColumn('people_leave_types', 'allows_future')) {
            Schema::table('people_leave_types', function (Blueprint $table) {
                $table->dropColumn('allows_future');
            });
        }
    }
};
