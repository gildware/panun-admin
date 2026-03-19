<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number');
            $table->foreignId('source_id')->nullable()->constrained('sources')->nullOnDelete();
            $table->string('lead_type', 64)->default('unknown'); // unknown, customer, provider, invalid, future_customer
            $table->dateTime('date_time_of_lead_received')->nullable();
            $table->foreignId('ad_source_id')->nullable()->constrained('adsources')->nullOnDelete();
            $table->string('handled_by', 64)->nullable();
            $table->text('remarks')->nullable();
            $table->dateTime('next_followup_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
