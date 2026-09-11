<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'hunting_platforms')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('hunting_platforms');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads') || Schema::hasColumn('leads', 'hunting_platforms')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->json('hunting_platforms')->nullable()->after('hunting_unpublish_notes');
        });
    }
};
