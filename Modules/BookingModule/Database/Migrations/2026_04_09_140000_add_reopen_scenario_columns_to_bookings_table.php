<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'reopen_completion_allowed')) {
                $table->boolean('reopen_completion_allowed')->default(false)->after('after_visit_cancel');
            }
            if (! Schema::hasColumn('bookings', 'reopen_disputed_snapshot')) {
                $table->json('reopen_disputed_snapshot')->nullable()->after('reopen_completion_allowed');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'reopen_disputed_snapshot')) {
                $table->dropColumn('reopen_disputed_snapshot');
            }
            if (Schema::hasColumn('bookings', 'reopen_completion_allowed')) {
                $table->dropColumn('reopen_completion_allowed');
            }
        });
    }
};
