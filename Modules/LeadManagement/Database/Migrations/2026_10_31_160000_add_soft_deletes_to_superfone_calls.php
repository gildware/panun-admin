<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('superfone_calls')) {
            return;
        }

        Schema::table('superfone_calls', function (Blueprint $table) {
            if (! Schema::hasColumn('superfone_calls', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('superfone_calls', 'deleted_by')) {
                $table->string('deleted_by', 64)->nullable()->after('deleted_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('superfone_calls')) {
            return;
        }

        Schema::table('superfone_calls', function (Blueprint $table) {
            if (Schema::hasColumn('superfone_calls', 'deleted_by')) {
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('superfone_calls', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
