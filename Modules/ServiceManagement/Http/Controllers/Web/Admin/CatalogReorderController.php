<?php

namespace Modules\ServiceManagement\Http\Controllers\Web\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Symfony\Component\HttpFoundation\Response;

class CatalogReorderController extends Controller
{
    public function categories(Request $request): JsonResponse
    {
        abort_unless(Gate::allows('category_update'), Response::HTTP_FORBIDDEN);

        $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'required|uuid',
        ]);

        $orderedIds = array_values($request->input('order', []));
        $existingIds = Category::query()
            ->ofType('main')
            ->whereIn('id', $orderedIds)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (! $this->idsMatchSet($orderedIds, $existingIds)) {
            return response()->json(['flag' => 0, 'message' => translate(DEFAULT_400['message'])], 422);
        }

        $this->applyMergedOrder(
            Category::query()->ofType('main')->ordered(),
            Category::class,
            $orderedIds
        );

        return response()->json(['flag' => 1, 'message' => translate(DEFAULT_UPDATE_200['message'])]);
    }

    public function subcategories(Request $request): JsonResponse
    {
        abort_unless(Gate::allows('category_update'), Response::HTTP_FORBIDDEN);

        $request->validate([
            'parent_id' => 'required|uuid',
            'order' => 'required|array|min:1',
            'order.*' => 'required|uuid',
        ]);

        $parentId = (string) $request->input('parent_id');
        $orderedIds = array_values($request->input('order', []));

        $existingIds = Category::query()
            ->ofType('sub')
            ->where('parent_id', $parentId)
            ->whereIn('id', $orderedIds)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (! $this->idsMatchSet($orderedIds, $existingIds)) {
            return response()->json(['flag' => 0, 'message' => translate(DEFAULT_400['message'])], 422);
        }

        $this->applyMergedOrder(
            Category::query()->ofType('sub')->where('parent_id', $parentId)->ordered(),
            Category::class,
            $orderedIds
        );

        return response()->json(['flag' => 1, 'message' => translate(DEFAULT_UPDATE_200['message'])]);
    }

    public function services(Request $request): JsonResponse
    {
        abort_unless(Gate::allows('service_update'), Response::HTTP_FORBIDDEN);

        $request->validate([
            'sub_category_id' => 'nullable|uuid',
            'category_id' => 'nullable|uuid',
            'order' => 'required|array|min:1',
            'order.*' => 'required|uuid',
        ]);

        $orderedIds = array_values($request->input('order', []));
        $subCategoryId = $request->input('sub_category_id');
        $categoryId = $request->input('category_id');

        $query = Service::query()->whereIn('id', $orderedIds);
        $siblingQuery = Service::query();

        if ($subCategoryId) {
            $query->where('sub_category_id', $subCategoryId);
            $siblingQuery->where('sub_category_id', $subCategoryId);
        } elseif ($categoryId) {
            $query->where('category_id', $categoryId)
                ->where(function ($q) {
                    $q->whereNull('sub_category_id')->orWhere('sub_category_id', '');
                });
            $siblingQuery->where('category_id', $categoryId)
                ->where(function ($q) {
                    $q->whereNull('sub_category_id')->orWhere('sub_category_id', '');
                });
        } else {
            return response()->json(['flag' => 0, 'message' => translate(DEFAULT_400['message'])], 422);
        }

        $existingIds = $query->pluck('id')->map(fn ($id) => (string) $id)->all();

        if (! $this->idsMatchSet($orderedIds, $existingIds)) {
            return response()->json(['flag' => 0, 'message' => translate(DEFAULT_400['message'])], 422);
        }

        $this->applyMergedOrder($siblingQuery->ordered(), Service::class, $orderedIds);

        return response()->json(['flag' => 1, 'message' => translate(DEFAULT_UPDATE_200['message'])]);
    }

    public function variations(Request $request): JsonResponse
    {
        abort_unless(Gate::allows('service_update'), Response::HTTP_FORBIDDEN);

        $request->validate([
            'service_id' => 'required|uuid',
            'order' => 'required|array|min:1',
            'order.*' => 'required|uuid',
        ]);

        $serviceId = (string) $request->input('service_id');
        $orderedIds = array_values($request->input('order', []));

        $existingIds = ServiceVariant::query()
            ->where('service_id', $serviceId)
            ->whereIn('id', $orderedIds)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (! $this->idsMatchSet($orderedIds, $existingIds)) {
            return response()->json(['flag' => 0, 'message' => translate(DEFAULT_400['message'])], 422);
        }

        $this->applyMergedOrder(
            ServiceVariant::query()->where('service_id', $serviceId)->orderBy('sort_order')->orderBy('created_at'),
            ServiceVariant::class,
            $orderedIds
        );

        return response()->json(['flag' => 1, 'message' => translate(DEFAULT_UPDATE_200['message'])]);
    }

    /**
     * @param  list<string>  $orderedIds
     * @param  list<string>  $existingIds
     */
    private function idsMatchSet(array $orderedIds, array $existingIds): bool
    {
        return count($orderedIds) === count($existingIds)
            && count(array_unique($orderedIds)) === count($orderedIds)
            && count(array_diff($orderedIds, $existingIds)) === 0
            && count(array_diff($existingIds, $orderedIds)) === 0;
    }

    /**
     * Merge a dragged subset order into the full sibling list so zone-filtered
     * catalog reorders do not collide with items not visible in the current view.
     *
     * @param  class-string  $modelClass
     * @param  list<string>  $orderedIds
     */
    private function applyMergedOrder(Builder $siblingQuery, string $modelClass, array $orderedIds): void
    {
        $fullIds = $siblingQuery
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $subset = array_fill_keys($orderedIds, true);
        $queue = $orderedIds;
        $merged = [];

        foreach ($fullIds as $id) {
            if (isset($subset[$id])) {
                $next = array_shift($queue);
                if ($next !== null) {
                    $merged[] = $next;
                }
            } else {
                $merged[] = $id;
            }
        }

        while ($queue !== []) {
            $merged[] = array_shift($queue);
        }

        DB::transaction(function () use ($modelClass, $merged) {
            foreach ($merged as $index => $id) {
                $modelClass::query()->where('id', $id)->update(['sort_order' => $index]);
            }
        });
    }
}
