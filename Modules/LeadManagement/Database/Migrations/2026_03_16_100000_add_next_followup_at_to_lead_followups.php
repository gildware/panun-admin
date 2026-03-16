<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_followups') && !Schema::hasColumn('lead_followups', 'next_followup_at')) {
            Schema::table('lead_followups', function (Blueprint $table) {
                $table->dateTime('next_followup_at')->nullable()->after('remarks');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lead_followups') && Schema::hasColumn('lead_followups', 'next_followup_at')) {
            Schema::table('lead_followups', function (Blueprint $table) {
                $table->dropColumn('next_followup_at');
            });
        }
    }
};
