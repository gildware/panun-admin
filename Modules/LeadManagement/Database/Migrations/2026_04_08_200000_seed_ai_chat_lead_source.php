<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\LeadManagement\Entities\Source;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('sources')
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(Source::NAME_AI_CHAT)])
            ->exists();

        if (!$exists) {
            DB::table('sources')->insert([
                'name' => Source::NAME_AI_CHAT,
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('sources')->where('name', Source::NAME_AI_CHAT)->delete();
    }
};
