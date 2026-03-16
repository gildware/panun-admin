<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('description');
        });

        Schema::table('adsources', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
        Schema::table('adsources', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
