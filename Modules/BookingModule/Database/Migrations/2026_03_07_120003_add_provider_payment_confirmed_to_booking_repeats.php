<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProviderPaymentConfirmedToBookingRepeats extends Migration
{
    public function up(): void
    {
        Schema::table('booking_repeats', function (Blueprint $table) {
            $table->timestamp('provider_payment_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('booking_repeats', function (Blueprint $table) {
            $table->dropColumn('provider_payment_confirmed_at');
        });
    }
}
