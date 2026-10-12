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
            if (! Schema::hasColumn('leads', 'non_responsive_at')) {
                $table->dateTime('non_responsive_at')->nullable()->after('next_followup_at');
            }
            if (! Schema::hasColumn('leads', 'non_responsive_by')) {
                $table->string('non_responsive_by', 64)->nullable()->after('non_responsive_at');
            }
            if (! Schema::hasColumn('leads', 'non_responsive_remarks')) {
                $table->text('non_responsive_remarks')->nullable()->after('non_responsive_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            foreach (['non_responsive_remarks', 'non_responsive_by', 'non_responsive_at'] as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
