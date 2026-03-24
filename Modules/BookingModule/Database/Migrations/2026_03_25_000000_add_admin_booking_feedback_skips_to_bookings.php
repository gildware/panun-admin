<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'admin_provider_feedback_skipped_at')) {
                $table->timestamp('admin_provider_feedback_skipped_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'admin_customer_feedback_skipped_at')) {
                $table->timestamp('admin_customer_feedback_skipped_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'admin_customer_feedback_skipped_at')) {
                $table->dropColumn('admin_customer_feedback_skipped_at');
            }
            if (Schema::hasColumn('bookings', 'admin_provider_feedback_skipped_at')) {
                $table->dropColumn('admin_provider_feedback_skipped_at');
            }
        });
    }
};
