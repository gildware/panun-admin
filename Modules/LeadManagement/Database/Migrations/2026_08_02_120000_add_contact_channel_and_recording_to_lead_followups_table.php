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
            if (! Schema::hasColumn('lead_followups', 'contact_channel')) {
                $table->string('contact_channel', 20)->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('lead_followups', 'recording_path')) {
                $table->string('recording_path')->nullable()->after('contact_channel');
            }
            if (! Schema::hasColumn('lead_followups', 'recording_disk')) {
                $table->string('recording_disk', 32)->nullable()->after('recording_path');
            }
            if (! Schema::hasColumn('lead_followups', 'recording_mime')) {
                $table->string('recording_mime', 128)->nullable()->after('recording_disk');
            }
            if (! Schema::hasColumn('lead_followups', 'recording_original_name')) {
                $table->string('recording_original_name')->nullable()->after('recording_mime');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lead_followups')) {
            return;
        }

        Schema::table('lead_followups', function (Blueprint $table) {
            $columns = [
                'contact_channel',
                'recording_path',
                'recording_disk',
                'recording_mime',
                'recording_original_name',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('lead_followups', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
