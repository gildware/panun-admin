<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public const ROLE_NAME = 'Employee';

    /** @var array<string, int> */
    private const ONBOARDING_PERMISSIONS = [
        'can_view' => 1,
        'can_update' => 1,
        'can_approve_or_deny' => 1,
    ];

    public function up(): void
    {
        $roleId = DB::table('roles')->where('role_name', self::ROLE_NAME)->value('id');
        if (! $roleId) {
            return;
        }

        $payload = array_merge([
            'role_id' => $roleId,
            'section_name' => 'onboarding_request',
            'can_view' => 0,
            'can_add' => 0,
            'can_update' => 0,
            'can_delete' => 0,
            'can_export' => 0,
            'can_manage_status' => 0,
            'can_approve_or_deny' => 0,
            'can_assign_serviceman' => 0,
            'can_give_feedback' => 0,
            'can_take_backup' => 0,
            'can_change_status' => 0,
            'updated_at' => now(),
        ], self::ONBOARDING_PERMISSIONS);

        $row = DB::table('role_accesses')
            ->where('role_id', $roleId)
            ->where('section_name', 'onboarding_request')
            ->first();

        if ($row) {
            DB::table('role_accesses')->where('id', $row->id)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('role_accesses')->insert($payload);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('role_name', self::ROLE_NAME)->value('id');
        if (! $roleId) {
            return;
        }

        DB::table('role_accesses')
            ->where('role_id', $roleId)
            ->where('section_name', 'onboarding_request')
            ->delete();
    }
};
