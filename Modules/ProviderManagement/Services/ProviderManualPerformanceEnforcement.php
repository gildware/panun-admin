<?php

namespace Modules\ProviderManagement\Services;

use Carbon\Carbon;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class ProviderManualPerformanceEnforcement
{
    public static function isPerformanceSuspended(Provider $provider): bool
    {
        $status = (string) ($provider->manual_performance_status ?? 'active');
        if ($status !== 'suspended') {
            return false;
        }

        $until = $provider->performance_suspended_until
            ? Carbon::parse($provider->performance_suspended_until)
            : null;

        return $until === null || $until->isFuture();
    }

    public static function isPerformanceBlacklisted(Provider $provider): bool
    {
        return (string) ($provider->manual_performance_status ?? 'active') === 'blacklisted';
    }

    public static function syncOwnerFromProvider(Provider $provider, ?User $owner = null): void
    {
        $owner ??= $provider->owner;
        if (! $owner) {
            return;
        }

        if ($owner->manual_performance_status === $provider->manual_performance_status
            && $owner->performance_suspended_until == $provider->performance_suspended_until) {
            return;
        }

        $owner->manual_performance_status = $provider->manual_performance_status;
        $owner->performance_suspended_until = $provider->performance_suspended_until;
        $owner->save();
    }

    public static function clearActiveSuspension(Provider $provider): void
    {
        $provider->manual_performance_status = 'active';
        $provider->performance_suspended_until = null;
        $provider->is_suspended = 0;
        $provider->performance_status = 'active';
        $provider->save();

        if ($owner = $provider->owner) {
            $owner->manual_performance_status = 'active';
            $owner->performance_suspended_until = null;
            $owner->save();
        }
    }

    public static function resolveLoginBlockMessage(User $user): ?string
    {
        $provider = $user->provider;

        if ($provider) {
            self::syncOwnerFromProvider($provider, $user);
        }

        $status = (string) ($provider?->manual_performance_status ?? $user->manual_performance_status ?? 'active');

        if ($status === 'blacklisted') {
            return translate('Provider account is blacklisted. Please contact with admin');
        }

        if ($status !== 'suspended') {
            return null;
        }

        $untilValue = $provider?->performance_suspended_until ?? $user->performance_suspended_until;
        $until = $untilValue ? Carbon::parse($untilValue) : null;

        if ($until && $until->isFuture()) {
            return translate('Provider account is suspended until') . ' ' . $until->format('Y-m-d H:i');
        }

        if ($provider) {
            $provider->manual_performance_status = 'active';
            $provider->performance_suspended_until = null;
            if ((int) $provider->is_active !== 0) {
                $provider->is_suspended = 0;
            }
            $provider->save();
        }

        $user->manual_performance_status = 'active';
        $user->performance_suspended_until = null;
        $user->save();

        return null;
    }
}
