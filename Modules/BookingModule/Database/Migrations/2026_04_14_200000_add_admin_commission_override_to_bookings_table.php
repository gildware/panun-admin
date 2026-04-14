<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('admin_commission_override', 14, 2)->nullable()->after('settlement_remarks');
        });

        $rows = DB::table('bookings')
            ->where('settlement_outcome', 'custom_commission')
            ->whereNotNull('settlement_config')
            ->get(['id', 'settlement_config']);

        foreach ($rows as $row) {
            $cfg = json_decode($row->settlement_config, true);
            if (! is_array($cfg)) {
                continue;
            }
            $amt = $cfg['custom_admin_commission'] ?? null;
            if (! is_numeric($amt)) {
                continue;
            }
            unset($cfg['custom_admin_commission']);
            $newCfg = $cfg === [] ? null : json_encode($cfg);
            DB::table('bookings')->where('id', $row->id)->update([
                'admin_commission_override' => round((float) $amt, 2),
                'settlement_outcome' => null,
                'settlement_config' => $newCfg,
                'settlement_snapshot' => null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('admin_commission_override');
        });
    }
};
