<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_followups') && !Schema::hasColumn('lead_followups', 'urgency')) {
            Schema::table('lead_followups', function (Blueprint $table) {
                $table->string('urgency', 10)->default('medium')->after('remarks');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lead_followups') && Schema::hasColumn('lead_followups', 'urgency')) {
            Schema::table('lead_followups', function (Blueprint $table) {
                $table->dropColumn('urgency');
            });
        }
    }
};
