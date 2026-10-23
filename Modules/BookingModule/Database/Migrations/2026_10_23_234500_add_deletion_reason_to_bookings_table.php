<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings') && ! Schema::hasColumn('bookings', 'deletion_reason')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->text('deletion_reason')->nullable()->after('deleted_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'deletion_reason')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('deletion_reason');
            });
        }
    }
};
