<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_cart_contacts')) {
            Schema::create('customer_cart_contacts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('customer_id');
                $table->uuid('contacted_by')->nullable();
                $table->dateTime('contacted_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
                $table->unique('customer_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_cart_contacts');
    }
};
