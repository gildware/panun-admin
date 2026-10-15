<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->text('areas_encompassed')->nullable()->after('description');
            $table->text('boundary_demarcation')->nullable()->after('areas_encompassed');
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn(['areas_encompassed', 'boundary_demarcation']);
        });
    }
};
