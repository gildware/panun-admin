<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lead_followups') || ! Schema::hasColumn('lead_followups', 'followup_at')) {
            return;
        }

        Schema::table('lead_followups', function (Blueprint $table) {
            $table->dateTime('followup_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lead_followups') || ! Schema::hasColumn('lead_followups', 'followup_at')) {
            return;
        }

        Schema::table('lead_followups', function (Blueprint $table) {
            $table->dateTime('followup_at')->nullable(false)->change();
        });
    }
};
