<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'customer_app_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('customer_app_access')->default(true)->after('user_type');
            });
        }

        // Provider and staff accounts must not use the customer app unless explicitly enabled.
        DB::table('users')->whereIn('user_type', [
            'provider-admin',
            'provider-employee',
            'provider-serviceman',
            'super-admin',
            'admin-employee',
        ])->update(['customer_app_access' => 0]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'customer_app_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('customer_app_access');
            });
        }
    }
};
