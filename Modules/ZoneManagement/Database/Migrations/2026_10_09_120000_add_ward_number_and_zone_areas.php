<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            if (! Schema::hasColumn('zones', 'ward_number')) {
                $table->unsignedSmallInteger('ward_number')->nullable()->after('name');
                $table->index('ward_number');
            }
        });

        if (Schema::hasTable('customer_lead_areas') && ! Schema::hasColumn('customer_lead_areas', 'zone_id')) {
            Schema::table('customer_lead_areas', function (Blueprint $table) {
                $table->dropUnique(['name']);
            });

            Schema::table('customer_lead_areas', function (Blueprint $table) {
                $table->foreignUuid('zone_id')->nullable()->after('name')->constrained('zones')->nullOnDelete();
                $table->unique(['zone_id', 'name']);
            });
        }

        DB::table('zones')
            ->where('description', 'like', 'Srinagar Municipal Corporation ward %')
            ->whereNull('ward_number')
            ->orderBy('id')
            ->select('id', 'description')
            ->each(function ($zone) {
                if (preg_match('/ward\s+(\d+)\s*$/', (string) $zone->description, $match)) {
                    DB::table('zones')->where('id', $zone->id)->update([
                        'ward_number' => (int) $match[1],
                    ]);
                }
            });

        if (! Schema::hasTable('customer_lead_areas') || ! Schema::hasColumn('zones', 'areas_encompassed')) {
            return;
        }

        DB::table('zones')
            ->whereNotNull('areas_encompassed')
            ->orderBy('id')
            ->select('id', 'areas_encompassed')
            ->each(function ($zone) {
                $names = preg_split('/\r\n|\r|\n/', (string) $zone->areas_encompassed) ?: [];
                $seen = [];
                foreach ($names as $name) {
                    $name = trim($name);
                    if ($name === '') {
                        continue;
                    }
                    $name = mb_substr($name, 0, 255);
                    $key = mb_strtolower($name);
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;

                    $exists = DB::table('customer_lead_areas')
                        ->where('zone_id', $zone->id)
                        ->whereRaw('LOWER(name) = ?', [$key])
                        ->exists();
                    if ($exists) {
                        continue;
                    }

                    DB::table('customer_lead_areas')->insert([
                        'name' => $name,
                        'zone_id' => $zone->id,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_lead_areas') && Schema::hasColumn('customer_lead_areas', 'zone_id')) {
            DB::table('customer_lead_areas')->whereNotNull('zone_id')->delete();

            Schema::table('customer_lead_areas', function (Blueprint $table) {
                $table->dropUnique(['zone_id', 'name']);
                $table->dropConstrainedForeignId('zone_id');
                $table->unique('name');
            });
        }

        Schema::table('zones', function (Blueprint $table) {
            if (Schema::hasColumn('zones', 'ward_number')) {
                $table->dropIndex(['ward_number']);
                $table->dropColumn('ward_number');
            }
        });
    }
};
