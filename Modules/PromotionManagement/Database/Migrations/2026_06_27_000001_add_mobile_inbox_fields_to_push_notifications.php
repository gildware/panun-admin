<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notifications', function (Blueprint $table) {
            $table->string('notification_type', 50)->nullable()->after('to_users');
            $table->uuid('booking_id')->nullable()->after('notification_type');
            $table->string('booking_type', 20)->nullable()->after('booking_id');
            $table->string('repeat_type', 20)->nullable()->after('booking_type');
        });

        Schema::table('push_notification_users', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_users', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });

        Schema::table('push_notifications', function (Blueprint $table) {
            $table->dropColumn(['notification_type', 'booking_id', 'booking_type', 'repeat_type']);
        });
    }
};
