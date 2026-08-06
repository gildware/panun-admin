<?php

namespace Modules\AdminModule\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\UserManagement\Entities\User;

class ImpersonationService
{
    public const SESSION_IMPERSONATOR_ID = 'impersonator_id';

    public const SESSION_FLAG = 'impersonating';

    public function canStart(User $actor): bool
    {
        return $actor->user_type === 'super-admin' && ! $this->isActive();
    }

    public function isActive(): bool
    {
        return (bool) session(self::SESSION_FLAG, false)
            && session()->has(self::SESSION_IMPERSONATOR_ID);
    }

    public function impersonator(): ?User
    {
        if (! $this->isActive()) {
            return null;
        }

        return User::query()->find(session(self::SESSION_IMPERSONATOR_ID));
    }

    public function start(User $impersonator, User $employee): void
    {
        if (! $this->canStart($impersonator)) {
            abort(403);
        }

        if ($employee->user_type !== 'admin-employee' || ! $employee->is_active) {
            abort(403);
        }

        $role = $employee->roles()->first();
        if (! $role || ! $role->is_active) {
            abort(403);
        }

        $impersonatorId = (string) $impersonator->id;

        Auth::guard('web')->login($employee, false);
        Auth::setUser($employee);

        session()->put([
            self::SESSION_IMPERSONATOR_ID => $impersonatorId,
            self::SESSION_FLAG => true,
        ]);
        session()->save();
    }

    public function leave(): ?User
    {
        if (! $this->isActive()) {
            return null;
        }

        $impersonator = $this->impersonator();
        session()->forget([self::SESSION_IMPERSONATOR_ID, self::SESSION_FLAG]);

        if (! $impersonator || $impersonator->user_type !== 'super-admin') {
            Auth::guard('web')->logout();

            return null;
        }

        Auth::guard('web')->login($impersonator, false);
        Auth::setUser($impersonator);
        session()->save();

        return $impersonator;
    }

    /**
     * First admin page this employee is allowed to open (same rules as real login).
     */
    public function landingUrl(?User $employee = null): string
    {
        $employee ??= auth()->user();

        $candidates = [
            ['route' => 'admin.dashboard', 'employee_only' => true],
            ['gate' => 'lead_view', 'route' => 'admin.lead.index', 'params' => ['handled_by' => ['__unassigned__']]],
            ['route' => 'admin.my-progress', 'employee_only' => true],
            ['gate' => 'booking_view', 'route' => 'admin.booking.list'],
            ['gate' => 'customer_view', 'route' => 'admin.customer.index'],
            ['gate' => 'provider_view', 'route' => 'admin.provider.list'],
            ['gate' => 'category_view', 'route' => 'admin.catalog.view'],
            ['gate' => 'service_view', 'route' => 'admin.service.index'],
            ['gate' => 'whatsapp_chat_view', 'route' => 'admin.whatsapp.conversations.index', 'params' => ['channel' => 'whatsapp', 'tab' => 'chats']],
        ];

        foreach ($candidates as $candidate) {
            if (! empty($candidate['employee_only']) && $employee->user_type !== 'admin-employee') {
                continue;
            }

            if (! isset($candidate['gate']) || Gate::allows($candidate['gate'])) {
                return route($candidate['route'], $candidate['params'] ?? []);
            }
        }

        return route('admin.profile_update');
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function impersonatableEmployees(?User $actor = null)
    {
        $actor ??= auth()->user();

        if (! $actor || ! $this->canStart($actor)) {
            return collect();
        }

        return User::query()
            ->where('user_type', 'admin-employee')
            ->where('is_active', 1)
            ->with(['roles' => fn ($query) => $query->where('is_active', 1)])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->filter(fn (User $employee) => $employee->roles->isNotEmpty())
            ->values();
    }
}
