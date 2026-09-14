<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_outbound_enquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('lead_outbound_enquiries', 'recording_path')) {
                $table->string('recording_path')->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('lead_outbound_enquiries', 'recording_disk')) {
                $table->string('recording_disk', 32)->nullable()->after('recording_path');
            }
            if (! Schema::hasColumn('lead_outbound_enquiries', 'recording_mime')) {
                $table->string('recording_mime', 128)->nullable()->after('recording_disk');
            }
            if (! Schema::hasColumn('lead_outbound_enquiries', 'recording_original_name')) {
                $table->string('recording_original_name')->nullable()->after('recording_mime');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lead_outbound_enquiries', function (Blueprint $table) {
            foreach (['recording_path', 'recording_disk', 'recording_mime', 'recording_original_name'] as $column) {
                if (Schema::hasColumn('lead_outbound_enquiries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
