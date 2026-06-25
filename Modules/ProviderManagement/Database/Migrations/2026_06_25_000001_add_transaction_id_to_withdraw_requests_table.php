<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('withdraw_requests', 'transaction_id')) {
                $table->string('transaction_id', 100)->nullable()->after('admin_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            if (Schema::hasColumn('withdraw_requests', 'transaction_id')) {
                $table->dropColumn('transaction_id');
            }
        });
    }
};
