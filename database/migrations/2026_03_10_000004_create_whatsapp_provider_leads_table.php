<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_provider_leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('lead_id')->unique();
            $table->string('phone', 50)->index();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('service')->nullable();
            $table->boolean('form_sent')->default(false);
            $table->string('status', 50)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_provider_leads');
    }
};

