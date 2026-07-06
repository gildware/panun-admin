<?php

namespace Modules\CustomerModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CustomerModule\Services\CustomerHomeCacheManager;

class CustomerHomeCacheController extends Controller
{
    public function resetAndWarm(Request $request): RedirectResponse
    {
        $zoneId = $request->input('zone_id');
        $zoneId = is_string($zoneId) && $zoneId !== '' ? $zoneId : null;

        $warmed = CustomerHomeCacheManager::resetAndWarm($zoneId, dispatchAsync: false);

        if ($warmed > 0) {
            Toastr::success(translate('Home_cache_reset_and_warmed_successfully'));
        } else {
            Toastr::success(translate('Home_cache_reset_rebuild_queued'));
        }

        return back();
    }
}
