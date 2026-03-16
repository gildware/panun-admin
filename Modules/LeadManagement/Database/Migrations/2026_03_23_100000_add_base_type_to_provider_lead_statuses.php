<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('provider_lead_statuses') && !Schema::hasColumn('provider_lead_statuses', 'base_type')) {
            Schema::table('provider_lead_statuses', function (Blueprint $table) {
                $table->string('base_type', 20)->default('pending')->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('provider_lead_statuses') && Schema::hasColumn('provider_lead_statuses', 'base_type')) {
            Schema::table('provider_lead_statuses', function (Blueprint $table) {
                $table->dropColumn('base_type');
            });
        }
    }
};

