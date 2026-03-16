<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads') && !Schema::hasColumn('leads', 'created_by')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('created_by', 64)->nullable()->after('next_followup_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'created_by')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('created_by');
            });
        }
    }
};
