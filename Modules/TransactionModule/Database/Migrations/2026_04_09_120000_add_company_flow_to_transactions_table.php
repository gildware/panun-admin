<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * IN / OUT = company is a counterparty; NONE = direct customer ↔ provider (no company money flow).
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('company_flow', 8)->nullable()->after('trx_type');
            $table->index('company_flow');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['company_flow']);
            $table->dropColumn('company_flow');
        });
    }
};
