<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'lead_temperature')) {
            return;
        }

        DB::table('leads')
            ->whereIn('lead_type', ['customer', 'provider'])
            ->where(function ($query) {
                $query->whereNull('lead_temperature')->orWhere('lead_temperature', '');
            })
            ->update(['lead_temperature' => 'warm']);

        if (! Schema::hasTable('lead_type_histories')) {
            return;
        }

        $latestIds = DB::table('lead_type_histories')
            ->selectRaw('MAX(id) as id')
            ->whereIn('type', ['customer', 'provider'])
            ->groupBy('lead_id')
            ->pluck('id');

        foreach ($latestIds->chunk(200) as $chunk) {
            $rows = DB::table('lead_type_histories')->whereIn('id', $chunk->all())->get(['id', 'data']);
            foreach ($rows as $row) {
                $data = json_decode((string) $row->data, true);
                if (! is_array($data)) {
                    $data = [];
                }
                if (! empty($data['lead_temperature'])) {
                    continue;
                }
                $data['lead_temperature'] = 'warm';
                DB::table('lead_type_histories')->where('id', $row->id)->update([
                    'data' => json_encode($data),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Warm is a real temperature choice, so this default is not reversed.
    }
};
