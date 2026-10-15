<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ZoneManagement\Entities\Zone;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('zones')
            || ! Schema::hasColumn('zones', 'boundary_demarcation')
            || ! Schema::hasColumn('zones', 'description')) {
            return;
        }

        DB::table('zones')
            ->select(['id', 'description', 'boundary_demarcation'])
            ->whereNotNull('boundary_demarcation')
            ->where('boundary_demarcation', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($zones): void {
                foreach ($zones as $zone) {
                    DB::table('zones')->where('id', $zone->id)->update([
                        'description' => Zone::descriptionIncludingBoundary(
                            $zone->description,
                            $zone->boundary_demarcation
                        ),
                        'boundary_demarcation' => null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Boundary text was merged into description and is not split back out.
    }
};
