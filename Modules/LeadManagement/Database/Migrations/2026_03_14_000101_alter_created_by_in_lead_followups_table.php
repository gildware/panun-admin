<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('lead_followups') && Schema::hasColumn('lead_followups', 'created_by')) {
            Schema::table('lead_followups', function (Blueprint $table) {
                // Change created_by to string to support UUID/string user IDs
                $table->string('created_by', 64)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lead_followups') && Schema::hasColumn('lead_followups', 'created_by')) {
            Schema::table('lead_followups', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->change();
            });
        }
    }
};

