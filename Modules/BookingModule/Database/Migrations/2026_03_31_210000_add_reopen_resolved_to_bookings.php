<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'reopen_resolved_at')) {
                $table->timestamp('reopen_resolved_at')->nullable()->after('reopened_by');
            }
            if (!Schema::hasColumn('bookings', 'reopen_resolved_by')) {
                $table->foreignUuid('reopen_resolved_by')
                    ->nullable()
                    ->after('reopen_resolved_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'reopen_resolved_by')) {
                $table->dropForeign(['reopen_resolved_by']);
                $table->dropColumn('reopen_resolved_by');
            }
            if (Schema::hasColumn('bookings', 'reopen_resolved_at')) {
                $table->dropColumn('reopen_resolved_at');
            }
        });
    }
};
