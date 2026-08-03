<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lead_followups') || Schema::hasColumn('lead_followups', 'followup_status')) {
            return;
        }

        Schema::table('lead_followups', function (Blueprint $table) {
            $table->string('followup_status', 20)->default('taken')->after('contact_channel');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lead_followups') || ! Schema::hasColumn('lead_followups', 'followup_status')) {
            return;
        }

        Schema::table('lead_followups', function (Blueprint $table) {
            $table->dropColumn('followup_status');
        });
    }
};
