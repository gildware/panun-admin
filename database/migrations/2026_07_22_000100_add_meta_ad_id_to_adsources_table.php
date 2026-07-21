<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('adsources')) {
            return;
        }

        Schema::table('adsources', function (Blueprint $table) {
            if (!Schema::hasColumn('adsources', 'meta_ad_id')) {
                $table->string('meta_ad_id', 64)->nullable()->after('id')->index();
            }
        });

        // Backfill meta_ad_id from CTWA description lines written earlier.
        $rows = DB::table('adsources')
            ->whereNull('meta_ad_id')
            ->whereNotNull('description')
            ->get(['id', 'description']);
        foreach ($rows as $row) {
            if (preg_match('/meta_source_id=([^\s\r\n]+)/', (string) $row->description, $m)) {
                $metaId = trim($m[1]);
                if ($metaId === '') {
                    continue;
                }
                // Skip if another row already owns this meta_ad_id.
                $exists = DB::table('adsources')->where('meta_ad_id', $metaId)->exists();
                if ($exists) {
                    continue;
                }
                DB::table('adsources')->where('id', $row->id)->update(['meta_ad_id' => $metaId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('adsources') && Schema::hasColumn('adsources', 'meta_ad_id')) {
            Schema::table('adsources', function (Blueprint $table) {
                $table->dropColumn('meta_ad_id');
            });
        }
    }
};
