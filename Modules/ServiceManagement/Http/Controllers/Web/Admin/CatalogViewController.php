<?php

namespace Modules\ServiceManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\ServiceManagement\Services\CatalogTreeService;
use Symfony\Component\HttpFoundation\Response;

class CatalogViewController extends Controller
{
    public function __construct(private readonly CatalogTreeService $catalogTreeService)
    {
    }

    public function index(Request $request): View|Factory|Application
    {
        abort_unless(Gate::any(['category_view', 'service_view']), Response::HTTP_FORBIDDEN);

        $request->validate([
            'zone_id' => 'nullable|uuid',
            'status' => 'nullable|in:all,active,inactive',
        ]);

        $zoneId = $request->input('zone_id');
        $status = $request->input('status', 'all');

        $payload = $this->catalogTreeService->shell($zoneId, $status);

        $currencyCode = business_config('currency_code', 'business_information')['live_values'] ?? 'USD';
        $currencySymbol = '$';
        foreach (CURRENCIES as $currency) {
            if ($currency['code'] === $currencyCode) {
                $currencySymbol = $currency['symbol'];
            }
        }

        return view('servicemanagement::admin.catalog.view', [
            'stats' => $payload['stats'],
            'tree' => $payload['tree'],
            'zoneTreeOptions' => $payload['zoneTreeOptions'],
            'zoneId' => $zoneId,
            'status' => $status,
            'currencySymbol' => $currencySymbol,
            'currencyPosition' => business_config('currency_symbol_position', 'business_information')['live_values'] ?? 'right',
            'currencyDecimalPoint' => (int) (business_config('currency_decimal_point', 'business_information')['live_values'] ?? 2),
            'canCategoryAdd' => Gate::allows('category_add'),
            'canServiceAdd' => Gate::allows('service_add'),
            'canServiceUpdate' => Gate::allows('service_update'),
        ]);
    }

    public function tree(Request $request): JsonResponse
    {
        abort_unless(Gate::any(['category_view', 'service_view']), Response::HTTP_FORBIDDEN);

        $request->validate([
            'zone_id' => 'nullable|uuid',
            'status' => 'nullable|in:all,active,inactive',
        ]);

        $payload = $this->catalogTreeService->build(
            $request->input('zone_id'),
            $request->input('status', 'all')
        );

        return response()->json($payload);
    }

    public function categories(Request $request): JsonResponse
    {
        abort_unless(Gate::any(['category_view', 'service_view']), Response::HTTP_FORBIDDEN);

        $request->validate([
            'zone_id' => 'required|uuid',
            'status' => 'nullable|in:all,active,inactive',
        ]);

        $items = $this->catalogTreeService->categories(
            (string) $request->input('zone_id'),
            (string) $request->input('status', 'all')
        );

        return response()->json(['items' => $items]);
    }

    public function subcategories(Request $request): JsonResponse
    {
        abort_unless(Gate::any(['category_view', 'service_view']), Response::HTTP_FORBIDDEN);

        $request->validate([
            'zone_id' => 'required|uuid',
            'category_id' => 'required|uuid',
            'status' => 'nullable|in:all,active,inactive',
        ]);

        $items = $this->catalogTreeService->subcategories(
            (string) $request->input('zone_id'),
            (string) $request->input('category_id'),
            (string) $request->input('status', 'all')
        );

        return response()->json(['items' => $items]);
    }

    public function services(Request $request): JsonResponse
    {
        abort_unless(Gate::any(['category_view', 'service_view']), Response::HTTP_FORBIDDEN);

        $request->validate([
            'zone_id' => 'required|uuid',
            'subcategory_id' => ['required', 'string', 'max:80'],
            'status' => 'nullable|in:all,active,inactive',
        ]);

        $items = $this->catalogTreeService->services(
            (string) $request->input('zone_id'),
            (string) $request->input('subcategory_id'),
            (string) $request->input('status', 'all')
        );

        return response()->json(['items' => $items]);
    }

    public function variations(Request $request): JsonResponse
    {
        abort_unless(Gate::any(['category_view', 'service_view']), Response::HTTP_FORBIDDEN);

        $request->validate([
            'zone_id' => 'required|uuid',
            'service_id' => 'required|uuid',
        ]);

        $items = $this->catalogTreeService->variations(
            (string) $request->input('zone_id'),
            (string) $request->input('service_id')
        );

        return response()->json(['items' => $items]);
    }
}
