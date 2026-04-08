<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_ai_settings', 'placeholder_provider_onboarding')) {
                $table->text('placeholder_provider_onboarding')->nullable();
            }
        });

        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('whatsapp_ai_settings', 'placeholder_custom_1')) {
                $drop[] = 'placeholder_custom_1';
            }
            if (Schema::hasColumn('whatsapp_ai_settings', 'placeholder_custom_2')) {
                $drop[] = 'placeholder_custom_2';
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_ai_settings', 'placeholder_provider_onboarding')) {
                $table->dropColumn('placeholder_provider_onboarding');
            }
        });

        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_ai_settings', 'placeholder_custom_1')) {
                $table->text('placeholder_custom_1')->nullable();
            }
            if (! Schema::hasColumn('whatsapp_ai_settings', 'placeholder_custom_2')) {
                $table->text('placeholder_custom_2')->nullable();
            }
        });
    }
};
