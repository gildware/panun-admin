<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\BookingModule\Entities\Booking;
use Modules\UserManagement\Entities\User;

class CustomerReferralEarningService
{
    public function referralRewardAmount(): float
    {
        return (float) (business_config('referral_value_per_currency_unit', 'customer_config')->live_values ?? 0);
    }

    public function isEnabled(): bool
    {
        return (int) (business_config('customer_referral_earning', 'customer_config')->live_values ?? 0) === 1;
    }

    public function referredUsersQuery(string $referrerId): Builder
    {
        return User::query()
            ->inCustomerDirectory()
            ->where('referred_by', $referrerId)
            ->withCount([
                'bookings as completed_bookings_count' => fn ($query) => $query->where('booking_status', 'completed'),
            ])
            ->select('users.*')
            ->selectSub(
                Booking::query()
                    ->select('updated_at')
                    ->whereColumn('customer_id', 'users.id')
                    ->where('booking_status', 'completed')
                    ->orderBy('updated_at')
                    ->limit(1),
                'first_completed_booking_at'
            );
    }

    public function buildSummary(string $referrerId, User $referrer): array
    {
        $reward = $this->referralRewardAmount();
        $baseQuery = $this->referredUsersQuery($referrerId);

        $totalReferred = (clone $baseQuery)->count();
        $completedFirstBooking = (clone $baseQuery)
            ->whereHas('bookings', fn ($q) => $q->where('booking_status', 'completed'))
            ->count();
        $pendingFirstBooking = max(0, $totalReferred - $completedFirstBooking);

        return [
            'referral_code' => $referrer->ref_code,
            'referral_reward_amount' => $reward,
            'total_referred' => $totalReferred,
            'completed_first_booking' => $completedFirstBooking,
            'pending_first_booking' => $pendingFirstBooking,
            'total_earned' => round($completedFirstBooking * $reward, 2),
            'total_pending' => round($pendingFirstBooking * $reward, 2),
        ];
    }

    public function paginateReferredUsers(string $referrerId, int $limit, int $offset): LengthAwarePaginator
    {
        return $this->referredUsersQuery($referrerId)
            ->latest('created_at')
            ->paginate($limit, ['*'], 'offset', $offset)
            ->withPath('');
    }

    public function transformReferredUser(User $user, float $reward): array
    {
        $completed = (int) ($user->completed_bookings_count ?? 0) > 0;

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => trim($user->first_name.' '.$user->last_name),
            'registered_at' => $user->created_at,
            'has_completed_first_booking' => $completed,
            'first_booking_completed_at' => $user->first_completed_booking_at,
            'earned_amount' => $completed ? round($reward, 2) : 0,
            'pending_amount' => $completed ? 0 : round($reward, 2),
        ];
    }
}
