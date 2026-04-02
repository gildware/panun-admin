<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('additional_charge_types', function (Blueprint $table) {
            $table->boolean('customizable_at_booking')->default(false)->after('is_active');
            $table->boolean('is_commissionable')->default(true)->after('customizable_at_booking');
        });
    }

    public function down(): void
    {
        Schema::table('additional_charge_types', function (Blueprint $table) {
            $table->dropColumn(['customizable_at_booking', 'is_commissionable']);
        });
    }
};
