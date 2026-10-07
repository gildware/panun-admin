<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin\Report;

use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\AdminModule\Services\CategoryBusinessReportAnalyticsService;
use Modules\CategoryManagement\Entities\Category;
use Modules\ZoneManagement\Entities\Zone;

class CategoryReportController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(Request $request, CategoryBusinessReportAnalyticsService $analytics): Renderable
    {
        if (! Gate::any(['report_view', 'lead_report_view'])) {
            throw new AuthorizationException();
        }

        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $view = $request->input('view', 'category') === 'subcategory' ? 'subcategory' : 'category';

        $selectedZoneIds = array_values(array_filter((array) $request->input('zone_ids', [])));
        $selectedCategoryIds = array_values(array_filter((array) $request->input('category_ids', [])));
        $selectedSubcategoryIds = array_values(array_filter((array) $request->input('subcategory_ids', [])));

        $report = $analytics->build($dateFrom, $dateTo, [
            'zone_ids' => $selectedZoneIds,
            'category_ids' => $selectedCategoryIds,
            'subcategory_ids' => $selectedSubcategoryIds,
        ]);

        $zones = Zone::withoutGlobalScopes()->ofStatus(1)->orderBy('name')->get(['id', 'name']);
        $categories = Category::withoutGlobalScopes()->ofType('main')->orderBy('name')->get(['id', 'name']);
        $subcategories = Category::withoutGlobalScopes()->ofType('sub')->orderBy('name')->get(['id', 'name', 'parent_id']);

        $queryParams = array_filter([
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'view' => $view,
            'zone_ids' => $selectedZoneIds ?: null,
            'category_ids' => $selectedCategoryIds ?: null,
            'subcategory_ids' => $selectedSubcategoryIds ?: null,
        ], fn ($value) => $value !== null && $value !== []);

        return view('adminmodule::admin.report.category', [
            'report' => $report,
            'slice' => $report[$view] ?? $report['category'],
            'view' => $view,
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            'zones' => $zones,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'categoryNames' => $categories->keyBy(fn ($category) => (string) $category->id),
            'selectedZoneIds' => $selectedZoneIds,
            'selectedCategoryIds' => $selectedCategoryIds,
            'selectedSubcategoryIds' => $selectedSubcategoryIds,
            'queryParams' => $queryParams,
            'splitDaily' => $view === 'subcategory' ? $selectedSubcategoryIds === [] : $selectedCategoryIds === [],
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
