<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<string, array<string, array<string, int>>> */
    private const ROLES = [
        'HR Manager' => [
            'dashboard' => ['can_view' => 1],
            'role' => ['can_view' => 1, 'can_add' => 1, 'can_update' => 1],
            'employee' => ['can_view' => 1, 'can_add' => 1, 'can_update' => 1, 'can_export' => 1, 'can_manage_status' => 1],
        ],
        'Accounts Manager' => [
            'dashboard' => ['can_view' => 1],
            'transaction' => ['can_view' => 1, 'can_export' => 1],
            'ledger' => ['can_view' => 1],
            'report' => ['can_view' => 1],
            'withdraw' => ['can_view' => 1],
        ],
    ];

    /** @var array<int, string> */
    private const FINANCE_SECTIONS = ['transaction', 'ledger'];

    public function up(): void
    {
        $this->revokeEmployeeFinance();

        $hrRoleId = $this->ensureRole('HR Manager', self::ROLES['HR Manager']);
        $accountsRoleId = $this->ensureRole('Accounts Manager', self::ROLES['Accounts Manager']);
        $employeeRoleId = DB::table('roles')->where('role_name', 'Employee')->value('id');

        $ownerId = DB::table('users')->where('user_type', 'super-admin')->orderBy('created_at')->value('id');

        $hrId = $this->ensureUser(
            'hr.manager@panunkaergar.com',
            'HR',
            'Manager',
            '+919800000011',
            'HrManager@2026',
            (string) $hrRoleId
        );
        $accountsId = $this->ensureUser(
            'accounts.manager@panunkaergar.com',
            'Accounts',
            'Manager',
            '+919800000012',
            'Accounts@2026',
            (string) $accountsRoleId
        );

        $this->ensureProfile($hrId, 'HR Manager', $ownerId ? (string) $ownerId : null);
        $this->ensureProfile($accountsId, 'Accounts Manager', $ownerId ? (string) $ownerId : null);

        if (! $employeeRoleId) {
            return;
        }

        $demoId = DB::table('users')->where('email', 'employee.demo@panunkaergar.com')->value('id');
        $juniorId = $this->ensureUser(
            'junior.employee@panunkaergar.com',
            'Junior',
            'Employee',
            '+919800000013',
            'Employee@2026',
            (string) $employeeRoleId
        );
        $this->ensureProfile($juniorId, 'Employee', $demoId ? (string) $demoId : null);
    }

    public function down(): void
    {
        $emails = [
            'hr.manager@panunkaergar.com',
            'accounts.manager@panunkaergar.com',
            'junior.employee@panunkaergar.com',
        ];
        $userIds = DB::table('users')->whereIn('email', $emails)->pluck('id');
        if ($userIds->isNotEmpty()) {
            if (Schema::hasTable('people_profiles')) {
                DB::table('people_profiles')->whereIn('user_id', $userIds)->delete();
            }
            DB::table('employee_role_sections')->whereIn('employee_id', $userIds)->delete();
            if (Schema::hasTable('user_addresses')) {
                DB::table('user_addresses')->whereIn('user_id', $userIds)->delete();
            }
            DB::table('users')->whereIn('id', $userIds)->delete();
        }

        foreach (array_keys(self::ROLES) as $roleName) {
            $roleId = DB::table('roles')->where('role_name', $roleName)->value('id');
            if (! $roleId) {
                continue;
            }
            DB::table('role_accesses')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }

    private function revokeEmployeeFinance(): void
    {
        $roleId = DB::table('roles')->where('role_name', 'Employee')->value('id');
        if (! $roleId) {
            return;
        }

        $cleared = [
            'can_view' => 0,
            'can_add' => 0,
            'can_update' => 0,
            'can_delete' => 0,
            'can_export' => 0,
            'can_manage_status' => 0,
            'can_approve_or_deny' => 0,
            'updated_at' => now(),
        ];

        DB::table('role_accesses')
            ->where('role_id', $roleId)
            ->whereIn('section_name', self::FINANCE_SECTIONS)
            ->update($cleared);

        if (! Schema::hasTable('employee_role_accesses')) {
            return;
        }

        $employeeIds = DB::table('employee_role_sections')->where('role_id', $roleId)->pluck('employee_id');
        if ($employeeIds->isEmpty()) {
            return;
        }

        DB::table('employee_role_accesses')
            ->where('role_id', $roleId)
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('section_name', self::FINANCE_SECTIONS)
            ->update($cleared);
    }

    /**
     * @param  array<string, array<string, int>>  $sections
     */
    private function ensureRole(string $name, array $sections): string
    {
        $existing = DB::table('roles')->where('role_name', $name)->first();
        if ($existing) {
            $roleId = (string) $existing->id;
        } else {
            $roleId = (string) Str::uuid();
            DB::table('roles')->insert([
                'id' => $roleId,
                'role_name' => $name,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($sections as $section => $flags) {
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
                'updated_at' => now(),
            ], $flags);

            $row = DB::table('role_accesses')
                ->where('role_id', $roleId)
                ->where('section_name', $section)
                ->first();

            if ($row) {
                DB::table('role_accesses')->where('id', $row->id)->update($payload);
            } else {
                $payload['created_at'] = now();
                DB::table('role_accesses')->insert($payload);
            }
        }

        return $roleId;
    }

    private function ensureUser(string $email, string $first, string $last, string $phone, string $password, string $roleId): string
    {
        $existing = DB::table('users')->where('email', $email)->first();
        if ($existing) {
            $userId = (string) $existing->id;
        } else {
            $userId = (string) Str::uuid();
            DB::table('users')->insert([
                'id' => $userId,
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($password),
                'profile_image' => 'default.png',
                'identification_number' => null,
                'identification_type' => 'nid',
                'identification_image' => json_encode([]),
                'user_type' => 'admin-employee',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $section = DB::table('employee_role_sections')->where('employee_id', $userId)->first();
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

        return $userId;
    }

    private function ensureProfile(string $userId, string $jobTitle, ?string $managerId): void
    {
        if (! Schema::hasTable('people_profiles')) {
            return;
        }

        if ($managerId === $userId) {
            $managerId = null;
        }

        $profile = DB::table('people_profiles')->where('user_id', $userId)->first();
        if ($profile) {
            DB::table('people_profiles')->where('user_id', $userId)->update([
                'job_title' => $jobTitle,
                'manager_id' => $managerId,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('people_profiles')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'employee_code' => $this->nextCode(),
            'job_title' => $jobTitle,
            'work_location' => '',
            'manager_id' => $managerId,
            'joined_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function nextCode(): string
    {
        $number = (int) DB::table('people_profiles')->count() + 1;
        do {
            $code = 'PK-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
            $number++;
        } while (DB::table('people_profiles')->where('employee_code', $code)->exists());

        return $code;
    }
};
