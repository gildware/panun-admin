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

    public static function isCashLimitSuspended(Provider $provider): bool
    {
        if ((int) ($provider->is_suspended ?? 0) !== 1) {
            return false;
        }

        if (! self::isCashLimitSuspensionConfigEnabled()) {
            return false;
        }

        return ! self::isPerformanceSuspended($provider) && ! self::isPerformanceBlacklisted($provider);
    }

    public static function isAdminManualSuspended(Provider $provider): bool
    {
        if ((int) ($provider->is_suspended ?? 0) !== 1) {
            return false;
        }

        if (self::isCashLimitSuspensionConfigEnabled()) {
            return false;
        }

        return ! self::isPerformanceSuspended($provider) && ! self::isPerformanceBlacklisted($provider);
    }

    private static function isCashLimitSuspensionConfigEnabled(): bool
    {
        $config = business_config('suspend_on_exceed_cash_limit_provider', 'provider_config');

        return (bool) ($config?->live_values ?? false);
    }

    /**
     * @return array{
     *     badge: string,
     *     label: string,
     *     items: list<array{
     *         type: string,
     *         label: string,
     *         reason: string,
     *         blocks_login: bool,
     *         blocks_bookings: bool,
     *         until: string|null,
     *         unsuspend_method: string|null
     *     }>
     * }
     */
    public static function providerListPerformance(Provider $provider): array
    {
        $summary = self::summarize($provider);
        $items = $summary['items'];
        $perfStatus = (string) ($provider->manual_performance_status ?? 'active');

        return [
            'badge' => match ($perfStatus) {
                'warning' => 'bg-warning',
                'active' => empty($items) ? 'bg-success' : 'bg-warning',
                default => 'bg-danger',
            },
            'label' => match ($perfStatus) {
                'warning' => translate('Warning'),
                'suspended' => translate('Suspended'),
                'blacklisted' => translate('Blacklisted'),
                default => empty($items) ? translate('Active') : translate('Restricted'),
            },
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     items: list<array{
     *         type: string,
     *         label: string,
     *         reason: string,
     *         blocks_login: bool,
     *         blocks_bookings: bool,
     *         until: string|null,
     *         unsuspend_method: string|null
     *     }>,
     *     blocks_login: bool,
     *     primary_reason: string|null
     * }
     */
    public static function summarize(Provider $provider): array
    {
        $items = [];

        if (self::isPerformanceBlacklisted($provider)) {
            $items[] = [
                'type' => 'blacklisted',
                'label' => translate('Blacklisted'),
                'reason' => translate('Provider blacklisted by admin due to performance policy. The provider cannot log in or receive bookings.'),
                'blocks_login' => true,
                'blocks_bookings' => true,
                'until' => null,
                'unsuspend_method' => 'performance_active',
            ];
        } elseif (self::isPerformanceSuspended($provider)) {
            $until = $provider->performance_suspended_until
                ? Carbon::parse($provider->performance_suspended_until)
                : null;

            $items[] = [
                'type' => 'performance_suspended',
                'label' => translate('Performance suspension'),
                'reason' => translate('Provider suspended by admin for performance review. This blocks provider app login until the suspension ends or admin restores access.'),
                'blocks_login' => true,
                'blocks_bookings' => true,
                'until' => $until?->format('Y-m-d H:i'),
                'unsuspend_method' => 'performance_active',
            ];
        }

        if (self::isCashLimitSuspended($provider)) {
            $items[] = [
                'type' => 'cash_limit',
                'label' => translate('Cash in hand suspension'),
                'reason' => translate('Your limit to hold cash is exceeded. Your account has been suspended until you pay the due. You will not receive any new booking requests from now'),
                'blocks_login' => false,
                'blocks_bookings' => true,
                'until' => null,
                'unsuspend_method' => 'cash_unsuspend',
            ];
        } elseif (self::isAdminManualSuspended($provider)) {
            $items[] = [
                'type' => 'admin_manual',
                'label' => translate('Suspended'),
                'reason' => translate('Your account has been temporarily suspended by Admin. Please contact our support team to learn more about the reason for this suspension and the steps to reinstate your account. We apologize for any inconvenience this may cause.'),
                'blocks_login' => false,
                'blocks_bookings' => true,
                'until' => null,
                'unsuspend_method' => 'cash_unsuspend',
            ];
        }

        $blocksLogin = collect($items)->contains(fn (array $item) => $item['blocks_login']);

        return [
            'items' => $items,
            'blocks_login' => $blocksLogin,
            'primary_reason' => $items[0]['reason'] ?? null,
        ];
    }

    public static function hasActiveRestrictions(Provider $provider): bool
    {
        return count(self::summarize($provider)['items']) > 0;
    }

    public static function primaryRestrictionLabel(Provider $provider): ?string
    {
        $items = self::summarize($provider)['items'];

        return $items[0]['label'] ?? null;
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
