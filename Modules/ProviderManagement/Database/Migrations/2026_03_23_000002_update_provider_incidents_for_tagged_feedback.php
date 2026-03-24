<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('provider_incidents')) {
            return;
        }

        Schema::table('provider_incidents', function (Blueprint $table) {
            if (!Schema::hasColumn('provider_incidents', 'action_type')) {
                $table->string('action_type', 32)->nullable()->index()->after('booking_id');
            }
            if (!Schema::hasColumn('provider_incidents', 'tags')) {
                $table->json('tags')->nullable()->after('incident_type');
            }
        });

        // Keep legacy enum values for backward compatibility and add unified categories.
        DB::statement("
            ALTER TABLE provider_incidents
            MODIFY incident_type ENUM(
                'NO_SHOW',
                'UNRESPONSIVE',
                'POOR_SERVICE',
                'LATE_ARRIVAL',
                'POSITIVE_FEEDBACK',
                'SUCCESSFUL_JOB',
                'COMPLAINT',
                'NON_COMPLAINT'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('provider_incidents')) {
            return;
        }

        // Revert enum to legacy values.
        DB::statement("
            ALTER TABLE provider_incidents
            MODIFY incident_type ENUM(
                'NO_SHOW',
                'UNRESPONSIVE',
                'POOR_SERVICE',
                'LATE_ARRIVAL',
                'POSITIVE_FEEDBACK',
                'SUCCESSFUL_JOB'
            ) NOT NULL
        ");

        Schema::table('provider_incidents', function (Blueprint $table) {
            if (Schema::hasColumn('provider_incidents', 'tags')) {
                $table->dropColumn('tags');
            }
            if (Schema::hasColumn('provider_incidents', 'action_type')) {
                $table->dropColumn('action_type');
            }
        });
    }
};

