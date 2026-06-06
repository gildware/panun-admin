<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_showcase_items', function (Blueprint $table) {
            // 2 = pending, 1 = approved, 0 = denied
            $table->unsignedTinyInteger('is_approved')->default(2)->after('is_active');
        });

        DB::table('provider_showcase_items')->update(['is_approved' => 1]);
    }

    public function down(): void
    {
        Schema::table('provider_showcase_items', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });
    }
};
