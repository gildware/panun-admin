<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (! Schema::hasColumn('leads', 'deleted_at')) {
                    $table->softDeletes();
                    $table->index('deleted_at');
                }
                if (! Schema::hasColumn('leads', 'deleted_by')) {
                    $table->string('deleted_by', 64)->nullable()->after('deleted_at');
                }
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (! Schema::hasColumn('bookings', 'deleted_at')) {
                    $table->softDeletes();
                    $table->index('deleted_at');
                }
                if (! Schema::hasColumn('bookings', 'deleted_by')) {
                    $table->string('deleted_by', 64)->nullable()->after('deleted_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (Schema::hasColumn('leads', 'deleted_by')) {
                    $table->dropColumn('deleted_by');
                }
                if (Schema::hasColumn('leads', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'deleted_by')) {
                    $table->dropColumn('deleted_by');
                }
                if (Schema::hasColumn('bookings', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
