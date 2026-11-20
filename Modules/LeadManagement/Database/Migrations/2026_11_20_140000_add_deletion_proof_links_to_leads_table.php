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
            if (! Schema::hasColumn('leads', 'deletion_reason_type')) {
                $table->string('deletion_reason_type', 40)->nullable()->after('deletion_reason');
            }
            if (! Schema::hasColumn('leads', 'deletion_linked_provider_id')) {
                $table->uuid('deletion_linked_provider_id')->nullable()->after('deletion_reason_type');
            }
            if (! Schema::hasColumn('leads', 'deletion_linked_lead_id')) {
                $table->unsignedBigInteger('deletion_linked_lead_id')->nullable()->after('deletion_linked_provider_id');
            }
            if (! Schema::hasColumn('leads', 'deletion_linked_booking_id')) {
                $table->uuid('deletion_linked_booking_id')->nullable()->after('deletion_linked_lead_id');
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
                'deletion_linked_booking_id',
                'deletion_linked_lead_id',
                'deletion_linked_provider_id',
                'deletion_reason_type',
            ] as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
