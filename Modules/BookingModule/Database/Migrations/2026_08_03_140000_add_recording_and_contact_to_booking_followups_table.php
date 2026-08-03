<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_followups', function (Blueprint $table) {
            $table->dateTime('followup_at')->nullable()->after('date');
            $table->dateTime('due_followup_at')->nullable()->after('followup_at');
            $table->dateTime('next_followup_at')->nullable()->after('due_followup_at');
            $table->string('contact_channel')->nullable()->after('remarks');
            $table->string('recording_path')->nullable()->after('contact_channel');
            $table->string('recording_disk')->nullable()->after('recording_path');
            $table->string('recording_mime')->nullable()->after('recording_disk');
            $table->string('recording_original_name')->nullable()->after('recording_mime');
            $table->text('recording_transcript')->nullable()->after('recording_original_name');
            $table->text('recording_summary')->nullable()->after('recording_transcript');
            $table->dateTime('transcribed_at')->nullable()->after('recording_summary');
        });
    }

    public function down(): void
    {
        Schema::table('booking_followups', function (Blueprint $table) {
            $table->dropColumn([
                'followup_at',
                'due_followup_at',
                'next_followup_at',
                'contact_channel',
                'recording_path',
                'recording_disk',
                'recording_mime',
                'recording_original_name',
                'recording_transcript',
                'recording_summary',
                'transcribed_at',
            ]);
        });
    }
};
