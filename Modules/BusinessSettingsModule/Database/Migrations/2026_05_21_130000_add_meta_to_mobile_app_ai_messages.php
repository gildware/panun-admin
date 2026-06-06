<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_app_ai_messages', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('mobile_app_ai_messages', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
