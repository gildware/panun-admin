<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_outbound_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('phone_number', 32);
            $table->string('contacted_through', 16); // message | call
            $table->string('status', 64)->default('pending');
            $table->dateTime('contacted_at')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['phone_number']);
            $table->index(['contacted_through']);
            $table->index(['status']);
            $table->index(['contacted_at']);
            $table->index(['created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_outbound_enquiries');
    }
};

