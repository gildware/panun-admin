<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_voice_followup_automation_runs', function (Blueprint $table) {
            $table->json('pending_candidates')->nullable()->after('campaign_ids');
            $table->timestamp('approved_at')->nullable()->after('finished_at');
            $table->foreignUuid('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_voice_followup_automation_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['pending_candidates', 'approved_at']);
        });
    }
};
