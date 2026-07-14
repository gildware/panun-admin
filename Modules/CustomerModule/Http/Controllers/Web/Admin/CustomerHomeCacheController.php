<?php

namespace Modules\CustomerModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CustomerModule\Services\CustomerHomeCacheManager;
use Modules\CustomerModule\Services\CustomerHomeCacheWarmState;

class CustomerHomeCacheController extends Controller
{
    public function resetAndWarm(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->boolean('check_only')) {
            return $this->jsonStatus();
        }

        $zoneId = $request->input('zone_id');
        $zoneId = is_string($zoneId) && $zoneId !== '' ? $zoneId : null;

        try {
            $warmed = CustomerHomeCacheManager::resetAndWarm($zoneId, dispatchAsync: true);
        } catch (\Throwable $e) {
            report($e);
            CustomerHomeCacheWarmState::markRebuildFailed(
                $e->getMessage() !== '' ? $e->getMessage() : translate('Failed_to_rebuild_home_cache')
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : translate('Failed_to_rebuild_home_cache'),
                    'rebuild' => CustomerHomeCacheWarmState::rebuildStatus(),
                ], 500);
            }

            throw $e;
        }

        $message = $warmed > 0
            ? translate('Home_cache_reset_and_warmed_successfully')
            : translate('Home_cache_reset_rebuild_queued');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'warmed' => $warmed,
                'queued' => $warmed === 0,
                'message' => $message,
                'needs_reset' => CustomerHomeCacheWarmState::needsAdminReminder(),
                'rebuild' => CustomerHomeCacheWarmState::rebuildStatus(),
            ]);
        }

        Toastr::success($message);

        return back();
    }

    private function jsonStatus(): JsonResponse
    {
        $rebuild = CustomerHomeCacheWarmState::rebuildStatus();

        return response()->json([
            'success' => true,
            'needs_reset' => CustomerHomeCacheWarmState::needsAdminReminder(),
            'current_version' => CustomerHomeCacheWarmState::currentVersion(),
            'last_warmed_version' => CustomerHomeCacheWarmState::lastWarmedVersion(),
            'rebuild' => $rebuild,
        ]);
    }

    public function status(): JsonResponse
    {
        return $this->jsonStatus();
    }
}
