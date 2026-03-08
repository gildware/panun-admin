<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->foreignUuid('created_by')->nullable()->after('reference_note')->comment('User who added the entry (admin/staff)');
        });
    }

    public function down(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
};
