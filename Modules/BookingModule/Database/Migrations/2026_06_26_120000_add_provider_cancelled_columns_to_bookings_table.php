<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'provider_cancelled_at')) {
                $table->timestamp('provider_cancelled_at')->nullable()->after('provider_id');
            }
            if (! Schema::hasColumn('bookings', 'provider_cancelled_by_provider_id')) {
                $table->uuid('provider_cancelled_by_provider_id')->nullable()->after('provider_cancelled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'provider_cancelled_by_provider_id')) {
                $table->dropColumn('provider_cancelled_by_provider_id');
            }
            if (Schema::hasColumn('bookings', 'provider_cancelled_at')) {
                $table->dropColumn('provider_cancelled_at');
            }
        });
    }
};
