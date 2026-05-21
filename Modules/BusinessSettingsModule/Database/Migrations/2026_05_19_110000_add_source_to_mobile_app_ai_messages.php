<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_app_ai_messages', function (Blueprint $table) {
            $table->string('source', 32)->default('mobile_app')->after('role');
            $table->index(['conversation_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('mobile_app_ai_messages', function (Blueprint $table) {
            $table->dropIndex(['conversation_id', 'source']);
            $table->dropColumn('source');
        });
    }
};
