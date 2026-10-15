<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads') || Schema::hasColumn('leads', 'lead_temperature')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->string('lead_temperature', 16)->nullable()->after('lead_type');
            $table->index('lead_temperature');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'lead_temperature')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['lead_temperature']);
            $table->dropColumn('lead_temperature');
        });
    }
};
