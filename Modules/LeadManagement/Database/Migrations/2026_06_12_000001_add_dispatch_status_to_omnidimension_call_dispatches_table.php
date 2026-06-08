<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('omnidimension_call_dispatches', function (Blueprint $table) {
            $table->string('dispatch_status', 64)->nullable()->after('omnidim_call_log_id');
        });
    }

    public function down(): void
    {
        Schema::table('omnidimension_call_dispatches', function (Blueprint $table) {
            $table->dropColumn('dispatch_status');
        });
    }
};
