<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'repeat_until_stopped')) {
                $table->boolean('repeat_until_stopped')->default(false)->after('is_repeated');
            }
            if (! Schema::hasColumn('bookings', 'repeat_stopped_at')) {
                $table->timestamp('repeat_stopped_at')->nullable()->after('repeat_until_stopped');
            }
            if (! Schema::hasColumn('bookings', 'repeat_cadence_meta')) {
                $table->json('repeat_cadence_meta')->nullable()->after('repeat_stopped_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'repeat_cadence_meta')) {
                $table->dropColumn('repeat_cadence_meta');
            }
            if (Schema::hasColumn('bookings', 'repeat_stopped_at')) {
                $table->dropColumn('repeat_stopped_at');
            }
            if (Schema::hasColumn('bookings', 'repeat_until_stopped')) {
                $table->dropColumn('repeat_until_stopped');
            }
        });
    }
};
