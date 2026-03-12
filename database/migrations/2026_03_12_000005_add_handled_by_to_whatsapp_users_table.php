<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->string('handled_by', 64)->nullable()->after('type'); // 'AI' or admin user id (UUID)
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->dropColumn('handled_by');
        });
    }
};

