<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_users')) {
            return;
        }
        if (! Schema::hasColumn('whatsapp_users', 'email')) {
            Schema::table('whatsapp_users', function (Blueprint $table) {
                $table->string('email', 191)->nullable()->after('alternate_phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('whatsapp_users') && Schema::hasColumn('whatsapp_users', 'email')) {
            Schema::table('whatsapp_users', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }
    }
};
