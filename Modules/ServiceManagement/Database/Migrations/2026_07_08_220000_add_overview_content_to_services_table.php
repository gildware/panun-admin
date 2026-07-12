<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'overview_content')) {
            Schema::table('services', function (Blueprint $table) {
                $table->json('overview_content')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'overview_content')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('overview_content');
            });
        }
    }
};
