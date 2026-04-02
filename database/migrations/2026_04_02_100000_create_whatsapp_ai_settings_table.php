<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_ai_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->boolean('use_full_custom_prompt')->default(false);
            $table->longText('custom_system_prompt')->nullable();
            $table->longText('allowed_policy')->nullable();
            $table->longText('forbidden_policy')->nullable();
            $table->longText('prompt_addendum')->nullable();
            $table->longText('flow_mermaid')->nullable();
            $table->timestamps();
        });

        DB::table('whatsapp_ai_settings')->insert([
            'id' => 1,
            'use_full_custom_prompt' => false,
            'custom_system_prompt' => null,
            'allowed_policy' => null,
            'forbidden_policy' => null,
            'prompt_addendum' => null,
            'flow_mermaid' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_ai_settings');
    }
};
