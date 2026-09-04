<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public const ROLE_NAME = 'Employee';

    /** @var array<string, array<string, int>> */
    private const SECTION_PERMISSIONS = [
        'provider' => ['can_view' => 1, 'can_add' => 1],
        'onboarding_request' => ['can_view' => 1, 'can_update' => 1, 'can_approve_or_deny' => 1],
    ];

    public function up(): void
    {
        foreach ($this->targetRoleIds() as $roleId) {
            $this->syncRoleAccess((string) $roleId);
        }
    }

    public function down(): void
    {
        // Keep granted provider access; this migration only expands employee reach.
    }

    /**
     * @return list<string>
     */
    private function targetRoleIds(): array
    {
        $ids = [];

        $namedRoleId = DB::table('roles')->where('role_name', self::ROLE_NAME)->value('id');
        if ($namedRoleId) {
            $ids[] = (string) $namedRoleId;
        }

        $employeeUserIds = DB::table('users')
            ->where('user_type', 'admin-employee')
            ->pluck('id');

        if ($employeeUserIds->isEmpty() || ! DB::getSchemaBuilder()->hasTable('employee_role_sections')) {
            return array_values(array_unique($ids));
        }

        $assignedRoleIds = DB::table('employee_role_sections')
            ->whereIn('employee_id', $employeeUserIds)
            ->pluck('role_id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->all();

        return array_values(array_unique(array_merge($ids, $assignedRoleIds)));
    }

    private function syncRoleAccess(string $roleId): void
    {
        foreach (self::SECTION_PERMISSIONS as $section => $flags) {
            $payload = array_merge([
                'role_id' => $roleId,
                'section_name' => $section,
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
            ], $flags);

            $row = DB::table('role_accesses')
                ->where('role_id', $roleId)
                ->where('section_name', $section)
                ->first();

            if ($row) {
                $updates = [];
                foreach ($flags as $column => $value) {
                    $updates[$column] = max((int) ($row->$column ?? 0), $value);
                }
                $updates['updated_at'] = now();
                DB::table('role_accesses')->where('id', $row->id)->update($updates);
            } else {
                $payload['created_at'] = now();
                DB::table('role_accesses')->insert($payload);
            }
        }
    }
};
