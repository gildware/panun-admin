<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('provider_showcase_items', 'denied_by')) {
            Schema::table('provider_showcase_items', function (Blueprint $table) {
                $table->uuid('denied_by')->nullable();
            });
        }

        if (! Schema::hasColumn('provider_showcase_items', 'denial_reason')) {
            Schema::table('provider_showcase_items', function (Blueprint $table) {
                $table->text('denial_reason')->nullable();
            });
        }

        if (! Schema::hasColumn('provider_showcase_items', 'approved_by')) {
            Schema::table('provider_showcase_items', function (Blueprint $table) {
                $table->uuid('approved_by')->nullable();
            });
        }

        if (! Schema::hasColumn('provider_showcase_items', 'approved_at')) {
            Schema::table('provider_showcase_items', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable();
            });
        }

        if (! Schema::hasColumn('provider_change_requests', 'denial_reason')) {
            Schema::table('provider_change_requests', function (Blueprint $table) {
                $table->text('denial_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('provider_change_requests', 'denial_reason')) {
            Schema::table('provider_change_requests', function (Blueprint $table) {
                $table->dropColumn('denial_reason');
            });
        }

        foreach (['approved_at', 'approved_by', 'denial_reason', 'denied_by'] as $column) {
            if (Schema::hasColumn('provider_showcase_items', $column)) {
                Schema::table('provider_showcase_items', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
