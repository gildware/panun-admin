<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('app_custom_requests') && ! Schema::hasColumn('app_custom_requests', 'customer_last_read_at')) {
            Schema::table('app_custom_requests', function (Blueprint $table) {
                $table->timestamp('customer_last_read_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('app_custom_requests') && Schema::hasColumn('app_custom_requests', 'customer_last_read_at')) {
            Schema::table('app_custom_requests', function (Blueprint $table) {
                $table->dropColumn('customer_last_read_at');
            });
        }
    }
};
