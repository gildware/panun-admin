<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'deletion_reason')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->text('deletion_reason')->nullable()->after('deleted_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'deletion_reason')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('deletion_reason');
            });
        }
    }
};
