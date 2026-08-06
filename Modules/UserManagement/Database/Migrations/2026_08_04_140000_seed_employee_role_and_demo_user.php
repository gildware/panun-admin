<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public const ROLE_NAME = 'Employee';

    public const DEMO_EMAIL = 'employee.demo@panunkaergar.com';

    public const DEMO_PASSWORD = 'Employee@2026';

    /** @var array<string, array<string, int>> */
    private const SECTION_PERMISSIONS = [
        'dashboard' => ['can_view' => 1],
        'lead' => ['can_view' => 1, 'can_add' => 1, 'can_update' => 1],
        'lead_outbound_enquiry' => ['can_view' => 1, 'can_add' => 1, 'can_update' => 1],
        'booking' => ['can_view' => 1, 'can_add' => 1, 'can_update' => 1, 'can_manage_status' => 1],
        'customer' => ['can_view' => 1, 'can_add' => 1, 'can_update' => 1],
        'provider' => ['can_view' => 1, 'can_add' => 1],
        'onboarding_request' => ['can_view' => 1, 'can_update' => 1, 'can_approve_or_deny' => 1],
        'category' => ['can_view' => 1],
        'service' => ['can_view' => 1],
        'zone' => ['can_view' => 1],
        'whatsapp_chat' => ['can_view' => 1, 'can_add' => 1, 'can_update' => 1],
        'transaction' => ['can_view' => 1],
        'ledger' => ['can_view' => 1],
    ];

    public function up(): void
    {
        $roleId = $this->ensureEmployeeRole();
        $this->syncRoleAccess($roleId);
        $this->ensureDemoEmployee($roleId);
    }

    public function down(): void
    {
        $user = DB::table('users')->where('email', self::DEMO_EMAIL)->first();
        if ($user) {
            DB::table('employee_role_sections')->where('employee_id', $user->id)->delete();
            DB::table('user_zones')->where('user_id', $user->id)->delete();
            if (Schema::hasTable('user_addresses')) {
                DB::table('user_addresses')->where('user_id', $user->id)->delete();
            }
            DB::table('users')->where('id', $user->id)->delete();
        }

        $role = DB::table('roles')->where('role_name', self::ROLE_NAME)->first();
        if ($role) {
            DB::table('role_accesses')->where('role_id', $role->id)->delete();
            DB::table('roles')->where('id', $role->id)->delete();
        }
    }

    private function ensureEmployeeRole(): string
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
                'created_at' => now(),
                'updated_at' => now(),
            ], $flags);

            $row = DB::table('role_accesses')
                ->where('role_id', $roleId)
                ->where('section_name', $section)
                ->first();

            if ($row) {
                unset($payload['created_at']);
                DB::table('role_accesses')->where('id', $row->id)->update($payload);
            } else {
                DB::table('role_accesses')->insert($payload);
            }
        }
    }

    private function ensureDemoEmployee(string $roleId): void
    {
        $existing = DB::table('users')->where('email', self::DEMO_EMAIL)->first();
        if ($existing) {
            $this->linkEmployeeToRole((string) $existing->id, $roleId);

            return;
        }

        $userId = (string) Str::uuid();
        $zoneId = DB::table('zones')->where('is_active', 1)->value('id');

        DB::table('users')->insert([
            'id' => $userId,
            'first_name' => 'Demo',
            'last_name' => 'Employee',
            'email' => self::DEMO_EMAIL,
            'phone' => '+919876543210',
            'password' => Hash::make(self::DEMO_PASSWORD),
            'profile_image' => 'default.png',
            'identification_number' => null,
            'identification_type' => 'nid',
            'identification_image' => json_encode([]),
            'user_type' => 'admin-employee',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($zoneId && Schema::hasTable('user_zones')) {
            DB::table('user_zones')->insert([
                'user_id' => $userId,
                'zone_id' => $zoneId,
            ]);
        }

        if (Schema::hasTable('user_addresses')) {
            DB::table('user_addresses')->insert([
                'user_id' => $userId,
                'address' => 'Demo employee address',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->linkEmployeeToRole($userId, $roleId);
    }

    private function linkEmployeeToRole(string $userId, string $roleId): void
    {
        $section = DB::table('employee_role_sections')
            ->where('employee_id', $userId)
            ->first();

        if ($section) {
            DB::table('employee_role_sections')
                ->where('employee_id', $userId)
                ->update(['role_id' => $roleId, 'updated_at' => now()]);
        } else {
            DB::table('employee_role_sections')->insert([
                'employee_id' => $userId,
                'role_id' => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
