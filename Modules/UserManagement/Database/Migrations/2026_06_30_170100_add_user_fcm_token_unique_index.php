<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_fcm_devices')) {
            return;
        }

        $duplicateGroups = DB::table('user_fcm_devices')
            ->select('user_id', 'fcm_token', DB::raw('COUNT(*) as row_count'))
            ->groupBy('user_id', 'fcm_token')
            ->having('row_count', '>', 1)
            ->get();

        foreach ($duplicateGroups as $group) {
            $ids = DB::table('user_fcm_devices')
                ->where('user_id', $group->user_id)
                ->where('fcm_token', $group->fcm_token)
                ->orderByDesc('last_seen_at')
                ->orderByDesc('updated_at')
                ->pluck('id');

            $ids->shift();

            if ($ids->isNotEmpty()) {
                DB::table('user_fcm_devices')->whereIn('id', $ids->all())->delete();
            }
        }

        Schema::table('user_fcm_devices', function (Blueprint $table) {
            $table->unique(['user_id', 'fcm_token'], 'user_fcm_devices_user_token_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_fcm_devices')) {
            return;
        }

        Schema::table('user_fcm_devices', function (Blueprint $table) {
            $table->dropUnique('user_fcm_devices_user_token_unique');
        });
    }
};
