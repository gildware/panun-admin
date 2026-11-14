<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'next_followup_reason')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->text('next_followup_reason')->nullable()->after('next_followup_at');
            });
        }

        if (Schema::hasTable('lead_followups') && ! Schema::hasColumn('lead_followups', 'next_followup_reason')) {
            Schema::table('lead_followups', function (Blueprint $table) {
                $table->text('next_followup_reason')->nullable()->after('next_followup_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lead_followups') && Schema::hasColumn('lead_followups', 'next_followup_reason')) {
            Schema::table('lead_followups', function (Blueprint $table) {
                $table->dropColumn('next_followup_reason');
            });
        }

        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'next_followup_reason')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('next_followup_reason');
            });
        }
    }
};
