<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('provider_type', 20)->default('company')->after('user_id');
        });

        // Backfill for any existing rows (safety for DBs that don't apply defaults to existing rows).
        DB::table('providers')
            ->whereNull('provider_type')
            ->update(['provider_type' => 'company']);
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('provider_type');
        });
    }
};

