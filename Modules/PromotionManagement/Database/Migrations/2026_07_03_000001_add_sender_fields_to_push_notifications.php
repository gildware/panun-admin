<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notifications', function (Blueprint $table) {
            $table->string('sender_type', 20)->nullable()->after('repeat_type');
            $table->uuid('sender_id')->nullable()->after('sender_type');
        });
    }

    public function down(): void
    {
        Schema::table('push_notifications', function (Blueprint $table) {
            $table->dropColumn(['sender_type', 'sender_id']);
        });
    }
};
