<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('providers', 'denied_by')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->uuid('denied_by')->nullable();
            });
        }

        if (! Schema::hasColumn('providers', 'denial_reason')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->text('denial_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('providers', 'denial_reason')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('denial_reason');
            });
        }

        if (Schema::hasColumn('providers', 'denied_by')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('denied_by');
            });
        }
    }
};
