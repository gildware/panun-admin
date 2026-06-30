<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_fcm_devices') || ! Schema::hasTable('users')) {
            return;
        }

        $now = now();

        DB::table('users')
            ->select(['id', 'fcm_token'])
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->where('fcm_token', '!=', '@')
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($now) {
                foreach ($users as $user) {
                    $token = (string) $user->fcm_token;
                    $exists = DB::table('user_fcm_devices')
                        ->where('user_id', $user->id)
                        ->where('fcm_token', $token)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('user_fcm_devices')->insert([
                        'id' => (string) Str::uuid(),
                        'user_id' => $user->id,
                        'device_id' => 'legacy:'.substr(hash('sha256', $token), 0, 32),
                        'fcm_token' => $token,
                        'platform' => null,
                        'last_seen_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Non-destructive data backfill; leave imported rows in place.
    }
};
