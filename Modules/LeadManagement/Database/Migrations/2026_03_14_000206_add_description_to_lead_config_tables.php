<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add description column to existing tables if they were created before
        if (Schema::hasTable('lead_invalid_reasons') && !Schema::hasColumn('lead_invalid_reasons', 'description')) {
            Schema::table('lead_invalid_reasons', function (Blueprint $table) {
                $table->text('description')->nullable()->after('name');
            });
        }

        if (Schema::hasTable('lead_future_customer_reasons') && !Schema::hasColumn('lead_future_customer_reasons', 'description')) {
            Schema::table('lead_future_customer_reasons', function (Blueprint $table) {
                $table->text('description')->nullable()->after('name');
            });
        }

        if (Schema::hasTable('lead_cancellation_reasons') && !Schema::hasColumn('lead_cancellation_reasons', 'description')) {
            Schema::table('lead_cancellation_reasons', function (Blueprint $table) {
                $table->text('description')->nullable()->after('name');
            });
        }

        if (Schema::hasTable('provider_lead_statuses') && !Schema::hasColumn('provider_lead_statuses', 'description')) {
            Schema::table('provider_lead_statuses', function (Blueprint $table) {
                $table->text('description')->nullable()->after('name');
            });
        }

        if (Schema::hasTable('districts') && !Schema::hasColumn('districts', 'description')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->text('description')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lead_invalid_reasons') && Schema::hasColumn('lead_invalid_reasons', 'description')) {
            Schema::table('lead_invalid_reasons', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasTable('lead_future_customer_reasons') && Schema::hasColumn('lead_future_customer_reasons', 'description')) {
            Schema::table('lead_future_customer_reasons', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasTable('lead_cancellation_reasons') && Schema::hasColumn('lead_cancellation_reasons', 'description')) {
            Schema::table('lead_cancellation_reasons', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasTable('provider_lead_statuses') && Schema::hasColumn('provider_lead_statuses', 'description')) {
            Schema::table('provider_lead_statuses', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasTable('districts') && Schema::hasColumn('districts', 'description')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};

