<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_fcm_devices', function (Blueprint $table) {
            if (! Schema::hasColumn('user_fcm_devices', 'device_model')) {
                $table->string('device_model', 128)->nullable()->after('platform');
            }
            if (! Schema::hasColumn('user_fcm_devices', 'device_manufacturer')) {
                $table->string('device_manufacturer', 128)->nullable()->after('device_model');
            }
            if (! Schema::hasColumn('user_fcm_devices', 'os_version')) {
                $table->string('os_version', 64)->nullable()->after('device_manufacturer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_fcm_devices', function (Blueprint $table) {
            $table->dropColumn(['device_model', 'device_manufacturer', 'os_version']);
        });
    }
};
