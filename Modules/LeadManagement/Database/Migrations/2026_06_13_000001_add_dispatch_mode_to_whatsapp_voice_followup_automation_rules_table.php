<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_voice_followup_automation_rules', function (Blueprint $table) {
            $table->string('dispatch_mode', 32)->default('approval')->after('retry_limit');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_voice_followup_automation_rules', function (Blueprint $table) {
            $table->dropColumn('dispatch_mode');
        });
    }
};
