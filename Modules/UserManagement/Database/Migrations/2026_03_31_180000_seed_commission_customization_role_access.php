<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SECTION_MAP = [
        'commission_custom_company' => 'business',
        'commission_custom_category' => 'category',
        'commission_custom_sub_category' => 'category',
        'commission_custom_service' => 'service',
        'commission_custom_provider' => 'provider',
    ];

    private const ACCESS_COLUMNS = [
        'can_view',
        'can_add',
        'can_update',
        'can_delete',
        'can_export',
        'can_manage_status',
        'can_approve_or_deny',
        'can_assign_serviceman',
        'can_give_feedback',
        'can_take_backup',
        'can_change_status',
    ];

    public function up(): void
    {
        $roleIds = DB::table('roles')->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach (self::SECTION_MAP as $newSection => $sourceSection) {
                $src = DB::table('role_accesses')
                    ->where('role_id', $roleId)
                    ->where('section_name', $sourceSection)
                    ->first();

                if (! $src) {
                    continue;
                }

                $payload = [
                    'role_id' => $roleId,
                    'section_name' => $newSection,
                    'updated_at' => now(),
                ];
                foreach (self::ACCESS_COLUMNS as $col) {
                    $payload[$col] = (int) ($src->$col ?? 0);
                }

                $existing = DB::table('role_accesses')
                    ->where('role_id', $roleId)
                    ->where('section_name', $newSection)
                    ->first();

                if ($existing) {
                    DB::table('role_accesses')
                        ->where('id', $existing->id)
                        ->update($payload);
                } else {
                    $payload['created_at'] = now();
                    DB::table('role_accesses')->insert($payload);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('role_accesses')
            ->whereIn('section_name', array_keys(self::SECTION_MAP))
            ->delete();
    }
};
