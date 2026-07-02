<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('providers', 'allow_advertisement')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->tinyInteger('allow_advertisement')->nullable()->default(null)->after('app_availability');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('providers', 'allow_advertisement')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('allow_advertisement');
            });
        }
    }
};
