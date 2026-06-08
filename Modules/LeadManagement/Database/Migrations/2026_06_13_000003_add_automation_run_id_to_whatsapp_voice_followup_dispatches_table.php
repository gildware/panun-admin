<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_voice_followup_dispatches', function (Blueprint $table) {
            $table->foreignId('automation_run_id')
                ->nullable()
                ->after('dispatched_by')
                ->constrained('whatsapp_voice_followup_automation_runs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_voice_followup_dispatches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('automation_run_id');
        });
    }
};
