<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lead_followups')) {
            return;
        }

        Schema::table('lead_followups', function (Blueprint $table) {
            if (! Schema::hasColumn('lead_followups', 'recording_transcript')) {
                $table->longText('recording_transcript')->nullable()->after('recording_original_name');
            }
            if (! Schema::hasColumn('lead_followups', 'recording_summary')) {
                $table->text('recording_summary')->nullable()->after('recording_transcript');
            }
            if (! Schema::hasColumn('lead_followups', 'transcribed_at')) {
                $table->dateTime('transcribed_at')->nullable()->after('recording_summary');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lead_followups')) {
            return;
        }

        Schema::table('lead_followups', function (Blueprint $table) {
            foreach (['recording_transcript', 'recording_summary', 'transcribed_at'] as $column) {
                if (Schema::hasColumn('lead_followups', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
