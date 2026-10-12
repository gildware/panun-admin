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
            if (! Schema::hasColumn('bookings', 'customer_non_responsive_at')) {
                $table->dateTime('customer_non_responsive_at')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'customer_non_responsive_by')) {
                $table->string('customer_non_responsive_by', 64)->nullable();
            }
            if (! Schema::hasColumn('bookings', 'customer_non_responsive_remarks')) {
                $table->text('customer_non_responsive_remarks')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            foreach (['customer_non_responsive_remarks', 'customer_non_responsive_by', 'customer_non_responsive_at'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
