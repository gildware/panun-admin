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
            if (! Schema::hasColumn('leads', 'hunting_status')) {
                $table->string('hunting_status', 32)->default('off')->after('next_followup_at');
            }
            if (! Schema::hasColumn('leads', 'hunting_started_at')) {
                $table->dateTime('hunting_started_at')->nullable()->after('hunting_status');
            }
            if (! Schema::hasColumn('leads', 'hunting_started_by')) {
                $table->string('hunting_started_by', 64)->nullable()->after('hunting_started_at');
            }
            if (! Schema::hasColumn('leads', 'hunting_unpublished_at')) {
                $table->dateTime('hunting_unpublished_at')->nullable()->after('hunting_started_by');
            }
            if (! Schema::hasColumn('leads', 'hunting_unpublished_by')) {
                $table->string('hunting_unpublished_by', 64)->nullable()->after('hunting_unpublished_at');
            }
            if (! Schema::hasColumn('leads', 'hunting_unpublish_reason')) {
                $table->string('hunting_unpublish_reason', 32)->nullable()->after('hunting_unpublished_by');
            }
            if (! Schema::hasColumn('leads', 'hunting_unpublish_notes')) {
                $table->text('hunting_unpublish_notes')->nullable()->after('hunting_unpublish_reason');
            }
        });

        try {
            Schema::table('leads', function (Blueprint $table) {
                $table->index(['lead_type', 'hunting_status'], 'leads_type_hunting_status_idx');
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            try {
                $table->dropIndex('leads_type_hunting_status_idx');
            } catch (\Throwable) {
            }

            foreach ([
                'hunting_status',
                'hunting_started_at',
                'hunting_started_by',
                'hunting_unpublished_at',
                'hunting_unpublished_by',
                'hunting_unpublish_reason',
                'hunting_unpublish_notes',
            ] as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
