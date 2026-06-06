<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('staff_presence_status', 20)->default('offline')->after('is_active');
            $table->timestamp('last_seen_at')->nullable()->after('staff_presence_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['staff_presence_status', 'last_seen_at']);
        });
    }
};
