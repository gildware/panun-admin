<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'manual_performance_status')) {
                $table->string('manual_performance_status')->nullable()->index();
            }
            if (!Schema::hasColumn('users', 'performance_suspended_until')) {
                $table->timestamp('performance_suspended_until')->nullable();
            }
        });

        Schema::table('providers', function (Blueprint $table) {
            if (!Schema::hasColumn('providers', 'manual_performance_status')) {
                $table->string('manual_performance_status')->nullable()->index();
            }
            if (!Schema::hasColumn('providers', 'performance_suspended_until')) {
                $table->timestamp('performance_suspended_until')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'performance_suspended_until')) {
                $table->dropColumn('performance_suspended_until');
            }
            if (Schema::hasColumn('users', 'manual_performance_status')) {
                $table->dropColumn('manual_performance_status');
            }
        });

        Schema::table('providers', function (Blueprint $table) {
            if (Schema::hasColumn('providers', 'performance_suspended_until')) {
                $table->dropColumn('performance_suspended_until');
            }
            if (Schema::hasColumn('providers', 'manual_performance_status')) {
                $table->dropColumn('manual_performance_status');
            }
        });
    }
};
