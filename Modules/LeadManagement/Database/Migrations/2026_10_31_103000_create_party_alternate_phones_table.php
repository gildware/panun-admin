<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('party_alternate_phones')) {
            return;
        }

        Schema::create('party_alternate_phones', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 20); // customer | provider | provider_lead
            $table->string('owner_id', 36); // user/provider uuid, or lead id for provider_lead
            $table->string('phone_digits', 20);
            $table->unsignedBigInteger('linked_from_lead_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->unique('phone_digits');
            $table->index(['owner_type', 'owner_id']);
            $table->index('linked_from_lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_alternate_phones');
    }
};
