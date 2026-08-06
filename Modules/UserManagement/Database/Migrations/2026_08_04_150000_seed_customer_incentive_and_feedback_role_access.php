<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SECTION_MAP = [
        'welcome_bonus' => 'customer',
        'referral_earning' => 'customer',
        'provider_feedback_config' => 'provider',
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

    private const EMPLOYEE_ROLE_NAME = 'Employee';

    /** @var list<string> */
    private const EMPLOYEE_DENIED_SECTIONS = [
        'welcome_bonus',
        'referral_earning',
        'provider_feedback_config',
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

        $employeeRoleId = DB::table('roles')
            ->where('role_name', self::EMPLOYEE_ROLE_NAME)
            ->value('id');

        if (! $employeeRoleId) {
            return;
        }

        DB::table('role_accesses')
            ->where('role_id', $employeeRoleId)
            ->where('section_name', 'provider')
            ->update([
                'can_add' => 1,
                'updated_at' => now(),
            ]);

        foreach (self::EMPLOYEE_DENIED_SECTIONS as $section) {
            $row = DB::table('role_accesses')
                ->where('role_id', $employeeRoleId)
                ->where('section_name', $section)
                ->first();

            $denyPayload = array_merge(
                ['updated_at' => now()],
                array_fill_keys(self::ACCESS_COLUMNS, 0)
            );

            if ($row) {
                DB::table('role_accesses')
                    ->where('id', $row->id)
                    ->update($denyPayload);
            } else {
                DB::table('role_accesses')->insert(array_merge([
                    'role_id' => $employeeRoleId,
                    'section_name' => $section,
                    'created_at' => now(),
                ], $denyPayload));
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
