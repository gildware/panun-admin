<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'initial_call_recording_path')) {
                $table->string('initial_call_recording_path')->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('leads', 'initial_call_recording_disk')) {
                $table->string('initial_call_recording_disk', 32)->nullable()->after('initial_call_recording_path');
            }
            if (! Schema::hasColumn('leads', 'initial_call_recording_mime')) {
                $table->string('initial_call_recording_mime', 128)->nullable()->after('initial_call_recording_disk');
            }
            if (! Schema::hasColumn('leads', 'initial_call_recording_original_name')) {
                $table->string('initial_call_recording_original_name')->nullable()->after('initial_call_recording_mime');
            }
            if (! Schema::hasColumn('leads', 'initial_call_recording_transcript')) {
                $table->longText('initial_call_recording_transcript')->nullable()->after('initial_call_recording_original_name');
            }
            if (! Schema::hasColumn('leads', 'initial_call_recording_summary')) {
                $table->text('initial_call_recording_summary')->nullable()->after('initial_call_recording_transcript');
            }
            if (! Schema::hasColumn('leads', 'initial_call_recording_transcribed_at')) {
                $table->dateTime('initial_call_recording_transcribed_at')->nullable()->after('initial_call_recording_summary');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            foreach ([
                'initial_call_recording_path',
                'initial_call_recording_disk',
                'initial_call_recording_mime',
                'initial_call_recording_original_name',
                'initial_call_recording_transcript',
                'initial_call_recording_summary',
                'initial_call_recording_transcribed_at',
            ] as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
