<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('superfone_calls', function (Blueprint $table) {
            $table->uuid('provider_id')->nullable()->after('lead_followup_id');
            $table->uuid('customer_user_id')->nullable()->after('provider_id');
            $table->uuid('booking_id')->nullable()->after('customer_user_id');
            $table->unsignedBigInteger('booking_followup_id')->nullable()->after('booking_id');

            $table->index('provider_id');
            $table->index('customer_user_id');
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('superfone_calls', function (Blueprint $table) {
            $table->dropIndex(['provider_id']);
            $table->dropIndex(['customer_user_id']);
            $table->dropIndex(['booking_id']);
            $table->dropColumn(['provider_id', 'customer_user_id', 'booking_id', 'booking_followup_id']);
        });
    }
};
