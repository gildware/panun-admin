<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('providers', 'approved_by')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->uuid('approved_by')->nullable();
            });
        }

        if (! Schema::hasColumn('providers', 'approved_at')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('providers', 'approved_at')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
        }

        if (Schema::hasColumn('providers', 'approved_by')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('approved_by');
            });
        }
    }
};
