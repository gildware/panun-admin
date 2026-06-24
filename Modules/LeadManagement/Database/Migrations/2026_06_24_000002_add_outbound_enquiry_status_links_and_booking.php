<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Entities\LeadOutboundEnquiryStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_outbound_enquiry_statuses', function (Blueprint $table) {
            $table->string('link_type', 16)->nullable()->after('name');

            $table->index(['link_type']);
        });

        Schema::table('lead_outbound_enquiries', function (Blueprint $table) {
            $table->foreignId('related_lead_id')
                ->nullable()
                ->after('lead_id')
                ->constrained('leads')
                ->nullOnDelete();

            $table->foreignUuid('booking_id')
                ->nullable()
                ->after('related_lead_id')
                ->constrained('bookings')
                ->nullOnDelete();

            $table->index(['related_lead_id']);
            $table->index(['booking_id']);
        });

        DB::table('lead_outbound_enquiry_statuses')
            ->whereRaw('LOWER(name) = ?', ['customer booked service'])
            ->update(['link_type' => LeadOutboundEnquiryStatus::LINK_BOOKING]);

        $exists = DB::table('lead_outbound_enquiry_statuses')
            ->whereRaw('LOWER(name) = ?', ['new lead created'])
            ->exists();

        if (!$exists) {
            DB::table('lead_outbound_enquiry_statuses')->insert([
                'name' => 'New lead created',
                'link_type' => LeadOutboundEnquiryStatus::LINK_LEAD,
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('lead_outbound_enquiry_statuses')
                ->whereRaw('LOWER(name) = ?', ['new lead created'])
                ->update(['link_type' => LeadOutboundEnquiryStatus::LINK_LEAD]);
        }
    }

    public function down(): void
    {
        Schema::table('lead_outbound_enquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_id');
            $table->dropConstrainedForeignId('related_lead_id');
        });

        Schema::table('lead_outbound_enquiry_statuses', function (Blueprint $table) {
            $table->dropColumn('link_type');
        });

        DB::table('lead_outbound_enquiry_statuses')
            ->whereRaw('LOWER(name) = ?', ['new lead created'])
            ->delete();
    }
};
