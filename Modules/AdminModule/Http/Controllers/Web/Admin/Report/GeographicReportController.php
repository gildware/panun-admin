<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin\Report;

use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\AdminModule\Services\GeographicBusinessReportAnalyticsService;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\CustomerLeadArea;
use Modules\ZoneManagement\Entities\Zone;

class GeographicReportController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(Request $request, GeographicBusinessReportAnalyticsService $analytics): Renderable
    {
        if (! Gate::any(['report_view', 'lead_report_view'])) {
            throw new AuthorizationException();
        }

        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $view = $request->input('view', 'area') === 'zone' ? 'zone' : 'area';

        $selectedZoneIds = array_values(array_filter((array) $request->input('zone_ids', [])));
        $selectedAreaIds = array_values(array_filter((array) $request->input('area_ids', [])));
        $selectedCategoryIds = array_values(array_filter((array) $request->input('category_ids', [])));

        $report = $analytics->build($dateFrom, $dateTo, [
            'zone_ids' => $selectedZoneIds,
            'area_ids' => $selectedAreaIds,
            'category_ids' => $selectedCategoryIds,
        ]);

        $zones = Zone::withoutGlobalScopes()->ofStatus(1)->orderBy('name')->get(['id', 'name']);
        $areas = CustomerLeadArea::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $categories = Category::withoutGlobalScopes()->ofType('main')->orderBy('name')->get(['id', 'name']);

        $queryParams = array_filter([
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'view' => $view,
            'zone_ids' => $selectedZoneIds ?: null,
            'area_ids' => $selectedAreaIds ?: null,
            'category_ids' => $selectedCategoryIds ?: null,
        ], fn ($value) => $value !== null && $value !== []);

        return view('adminmodule::admin.report.geographic', [
            'report' => $report,
            'geo' => $report[$view] ?? $report['area'],
            'view' => $view,
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            'zones' => $zones,
            'areas' => $areas,
            'categories' => $categories,
            'selectedZoneIds' => $selectedZoneIds,
            'selectedAreaIds' => $selectedAreaIds,
            'selectedCategoryIds' => $selectedCategoryIds,
            'queryParams' => $queryParams,
            'splitDailyByGeo' => $view === 'area' ? $selectedAreaIds === [] : $selectedZoneIds === [],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $from = $request->input('date_from');
        $to = $request->input('date_to');

        if (! $from || ! $to) {
            return [Carbon::now()->startOfMonth(), Carbon::now()->endOfDay()];
        }

        try {
            $start = Carbon::parse($from)->startOfDay();
            $end = Carbon::parse($to)->endOfDay();
        } catch (\Throwable) {
            return [Carbon::now()->startOfMonth(), Carbon::now()->endOfDay()];
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }
}
