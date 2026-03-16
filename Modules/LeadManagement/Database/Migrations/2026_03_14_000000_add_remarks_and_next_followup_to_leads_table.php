<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'remarks')) {
                $table->text('remarks')->nullable()->after('handled_by');
            }
            if (!Schema::hasColumn('leads', 'next_followup_at')) {
                $table->dateTime('next_followup_at')->nullable()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'next_followup_at')) {
                $table->dropColumn('next_followup_at');
            }
            if (Schema::hasColumn('leads', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });
    }
};

