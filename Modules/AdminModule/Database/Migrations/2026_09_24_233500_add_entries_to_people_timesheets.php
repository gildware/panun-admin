<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people_timesheets', function (Blueprint $table) {
            $table->json('entries')->nullable()->after('hours');
        });
    }

    public function down(): void
    {
        Schema::table('people_timesheets', function (Blueprint $table) {
            $table->dropColumn('entries');
        });
    }
};
