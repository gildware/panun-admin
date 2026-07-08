<?php

namespace Modules\ServiceManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Services\ServiceOverviewContentResolver;
use Modules\ServiceManagement\Services\ServiceOverviewDefaultsService;
use Modules\ServiceManagement\Support\ServiceOverviewIconPresets;

class ServiceOverviewContentController extends Controller
{
  use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

  public function defaults(): View
  {
    $this->authorize('service_update');
    $defaults = ServiceOverviewDefaultsService::get();
    $iconOptions = ServiceOverviewIconPresets::options();

    return view('servicemanagement::admin.overview-defaults', compact('defaults', 'iconOptions'));
  }

  public function updateDefaults(Request $request): RedirectResponse
  {
    $this->authorize('service_update');
    $payload = $request->input('overview_defaults');
    if (is_string($payload)) {
      $payload = json_decode($payload, true);
    }

    if (! is_array($payload)) {
      Toastr::error(translate(DEFAULT_400['message']));

      return back();
    }

    ServiceOverviewDefaultsService::save($payload);
    Toastr::success(translate(DEFAULT_UPDATE_200['message']));

    return back();
  }

  public function update(Request $request, string $serviceId): JsonResponse
  {
    $this->authorize('service_update');
    $service = Service::query()->withoutGlobalScope('translate')->find($serviceId);
    if (! $service) {
      return response()->json(['flag' => 0, 'message' => translate(DEFAULT_204['message'])], 404);
    }

    $payload = $request->input('overview_content');
    if (is_string($payload)) {
      $payload = json_decode($payload, true);
    }

    if (! is_array($payload)) {
      return response()->json(['flag' => 0, 'message' => translate(DEFAULT_400['message'])], 422);
    }

    $normalized = ServiceOverviewContentResolver::normalizeServiceContent($payload);
    $service->overview_content = $normalized;
    $service->save();

    $resolved = ServiceOverviewContentResolver::resolveForService($service);

    return response()->json([
      'flag' => 1,
      'message' => translate(DEFAULT_UPDATE_200['message']),
      'overview_content' => $normalized,
      'resolved_overview_content' => $resolved,
    ]);
  }
}
