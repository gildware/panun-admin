<?php

namespace Modules\CustomerModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CustomerModule\Services\CustomerHomeCacheManager;

class CustomerHomeCacheController extends Controller
{
    public function resetAndWarm(Request $request): RedirectResponse|JsonResponse
    {
        $zoneId = $request->input('zone_id');
        $zoneId = is_string($zoneId) && $zoneId !== '' ? $zoneId : null;

        $warmed = CustomerHomeCacheManager::resetAndWarm($zoneId, dispatchAsync: false);

        $message = $warmed > 0
            ? translate('Home_cache_reset_and_warmed_successfully')
            : translate('Home_cache_reset_rebuild_queued');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'warmed' => $warmed,
                'message' => $message,
            ]);
        }

        if ($warmed > 0) {
            Toastr::success($message);
        } else {
            Toastr::success($message);
        }

        return back();
    }
}
