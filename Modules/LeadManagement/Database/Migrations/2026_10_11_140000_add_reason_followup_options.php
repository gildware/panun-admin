<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'lead_invalid_reasons',
            'lead_cancellation_reasons',
            'provider_cancellation_reasons',
            'lead_future_provider_reasons',
        ] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'followup_label')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('followup_label')->nullable()->after('description');
                });
            }
        }

        if (! Schema::hasTable('lead_reason_followup_options')) {
            Schema::create('lead_reason_followup_options', function (Blueprint $table) {
                $table->id();
                $table->string('reason_type', 64);
                $table->unsignedBigInteger('reason_id');
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['reason_type', 'reason_id'], 'lead_reason_followup_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_reason_followup_options');

        foreach ([
            'lead_invalid_reasons',
            'lead_cancellation_reasons',
            'provider_cancellation_reasons',
            'lead_future_provider_reasons',
        ] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'followup_label')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('followup_label');
                });
            }
        }
    }
};
