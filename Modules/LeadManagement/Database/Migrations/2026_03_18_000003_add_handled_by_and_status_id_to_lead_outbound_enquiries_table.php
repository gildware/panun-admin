<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_outbound_enquiries', function (Blueprint $table) {
            $table->foreignUuid('handled_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->after('handled_by')->constrained('lead_outbound_enquiry_statuses')->nullOnDelete();

            $table->index(['handled_by']);
            $table->index(['status_id']);
        });
    }

    public function down(): void
    {
        Schema::table('lead_outbound_enquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_id');
            $table->dropConstrainedForeignId('handled_by');
        });
    }
};

