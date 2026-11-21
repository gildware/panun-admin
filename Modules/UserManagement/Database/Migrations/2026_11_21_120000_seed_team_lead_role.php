<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public const ROLE_NAME = 'Team Lead';

    public const SOURCE_ROLE_NAME = 'Customer Support';

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_accesses')) {
            return;
        }

        $roleId = $this->ensureRole();
        $this->syncAccessFromSource($roleId);
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $roleId = DB::table('roles')->where('role_name', self::ROLE_NAME)->value('id');
        if (! $roleId) {
            return;
        }

        if (Schema::hasTable('employee_role_accesses')) {
            DB::table('employee_role_accesses')->where('role_id', $roleId)->delete();
        }
        if (Schema::hasTable('employee_role_sections')) {
            DB::table('employee_role_sections')->where('role_id', $roleId)->delete();
        }
        DB::table('role_accesses')->where('role_id', $roleId)->delete();
        DB::table('roles')->where('id', $roleId)->delete();
    }

    private function ensureRole(): string
    {
        $existing = DB::table('roles')->where('role_name', self::ROLE_NAME)->first();
        if ($existing) {
            return (string) $existing->id;
        }

        $roleId = (string) Str::uuid();
        DB::table('roles')->insert([
            'id' => $roleId,
            'role_name' => self::ROLE_NAME,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $roleId;
    }

    private function syncAccessFromSource(string $roleId): void
    {
        $sourceRoleId = DB::table('roles')->where('role_name', self::SOURCE_ROLE_NAME)->value('id');
        if (! $sourceRoleId) {
            return;
        }

        $sourceRows = DB::table('role_accesses')->where('role_id', $sourceRoleId)->get();
        foreach ($sourceRows as $row) {
            $payload = [
                'role_id' => $roleId,
                'section_name' => $row->section_name,
                'can_view' => (int) ($row->can_view ?? 0),
                'can_add' => (int) ($row->can_add ?? 0),
                'can_update' => (int) ($row->can_update ?? 0),
                'can_delete' => (int) ($row->can_delete ?? 0),
                'can_export' => (int) ($row->can_export ?? 0),
                'can_manage_status' => (int) ($row->can_manage_status ?? 0),
                'can_approve_or_deny' => (int) ($row->can_approve_or_deny ?? 0),
                'can_assign_serviceman' => (int) ($row->can_assign_serviceman ?? 0),
                'can_give_feedback' => (int) ($row->can_give_feedback ?? 0),
                'can_take_backup' => (int) ($row->can_take_backup ?? 0),
                'can_change_status' => (int) ($row->can_change_status ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $existing = DB::table('role_accesses')
                ->where('role_id', $roleId)
                ->where('section_name', $row->section_name)
                ->first();

            if ($existing) {
                unset($payload['created_at']);
                DB::table('role_accesses')->where('id', $existing->id)->update($payload);
            } else {
                DB::table('role_accesses')->insert($payload);
            }
        }
    }
};
