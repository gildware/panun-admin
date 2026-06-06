<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Cache;
use Modules\UserManagement\Entities\User;

/**
 * Per-user daily limits for messages and Gemini calls.
 */
class MobileAppAiCostGuardService
{
    public function checkMessageAllowed(User $user): ?string
    {
        if (! config('mobile_app_ai_production.cost.enabled', true)) {
            return null;
        }

        $limit = (int) config('mobile_app_ai_production.cost.messages_per_user_per_day', 200);
        $key = $this->dayKey('msg', $user->id);
        $count = (int) Cache::get($key, 0);
        if ($count >= $limit) {
            return 'You\'ve reached today\'s AI message limit. Please try again tomorrow or use **Cart** and **Bookings** in the app.';
        }

        Cache::put($key, $count + 1, now()->endOfDay());

        return null;
    }

    public function checkGeminiAllowed(User $user): bool
    {
        if (! config('mobile_app_ai_production.cost.enabled', true)) {
            return true;
        }

        $limit = (int) config('mobile_app_ai_production.cost.gemini_calls_per_user_per_day', 80);
        $key = $this->dayKey('gemini', $user->id);
        $count = (int) Cache::get($key, 0);
        if ($count >= $limit) {
            return false;
        }

        Cache::put($key, $count + 1, now()->endOfDay());

        return true;
    }

    private function dayKey(string $type, string|int $userId): string
    {
        return 'mobile_app_ai_cost:'.$type.':'.$userId.':'.now()->format('Y-m-d');
    }
}
