<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('providers')
            ->whereNotNull('zone_id')
            ->orderBy('id')
            ->chunk(200, function ($providers) {
                $now = now();
                foreach ($providers as $p) {
                    $exists = DB::table('provider_zone')
                        ->where('provider_id', $p->id)
                        ->where('zone_id', $p->zone_id)
                        ->exists();
                    if (! $exists) {
                        DB::table('provider_zone')->insert([
                            'provider_id' => $p->id,
                            'zone_id' => $p->zone_id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('provider_zone')->truncate();
    }
};
