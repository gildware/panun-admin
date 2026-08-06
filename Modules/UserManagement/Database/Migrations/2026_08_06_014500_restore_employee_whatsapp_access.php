<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public const ROLE_NAME = 'Employee';

    /** @var array<string, int> */
    private const WHATSAPP_PERMISSIONS = [
        'can_view' => 1,
        'can_add' => 1,
        'can_update' => 1,
        'can_delete' => 0,
        'can_export' => 0,
        'can_manage_status' => 0,
        'can_approve_or_deny' => 0,
        'can_assign_serviceman' => 0,
        'can_give_feedback' => 0,
        'can_take_backup' => 0,
        'can_change_status' => 0,
    ];

    public function up(): void
    {
        $roleId = DB::table('roles')->where('role_name', self::ROLE_NAME)->value('id');
        if (! $roleId) {
            return;
        }

        $payload = array_merge([
            'role_id' => $roleId,
            'section_name' => 'whatsapp_chat',
            'created_at' => now(),
            'updated_at' => now(),
        ], self::WHATSAPP_PERMISSIONS);

        $row = DB::table('role_accesses')
            ->where('role_id', $roleId)
            ->where('section_name', 'whatsapp_chat')
            ->first();

        if ($row) {
            unset($payload['created_at']);
            DB::table('role_accesses')->where('id', $row->id)->update($payload);
        } else {
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
            ->where('section_name', 'whatsapp_chat')
            ->delete();
    }
};
