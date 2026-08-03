<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('lead_followups') || Schema::hasColumn('lead_followups', 'due_followup_at')) {
            return;
        }

        Schema::table('lead_followups', function (Blueprint $table) {
            $table->dateTime('due_followup_at')->nullable()->after('followup_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lead_followups') || ! Schema::hasColumn('lead_followups', 'due_followup_at')) {
            return;
        }

        Schema::table('lead_followups', function (Blueprint $table) {
            $table->dropColumn('due_followup_at');
        });
    }
};
