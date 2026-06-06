<?php

namespace Modules\Auth\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Auth\Services\ProviderRegistrationCatalogService;

class RegistrationCatalogController extends Controller
{
    public function __construct(private ProviderRegistrationCatalogService $catalog) {}

    public function categories(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => 'uuid',
            'zone_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $catalogZoneIds = $this->catalog->catalogZoneIdsFromRequest($request);
        if ($catalogZoneIds === []) {
            return response()->json(response_formatter(DEFAULT_400, null, [[
                'error_code' => 'zone_ids',
                'message' => translate('Select_Zone'),
            ]]), 400);
        }

        $categories = $this->catalog->categoriesQuery($catalogZoneIds)
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $categories), 200);
    }

    public function subCategories(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'category_id' => 'required|uuid',
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => 'uuid',
            'zone_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $catalogZoneIds = $this->catalog->catalogZoneIdsFromRequest($request);
        if ($catalogZoneIds === []) {
            return response()->json(response_formatter(DEFAULT_400, null, [[
                'error_code' => 'zone_ids',
                'message' => translate('Select_Zone'),
            ]]), 400);
        }

        $childes = $this->catalog->subCategoriesQuery((string) $request['category_id'], $catalogZoneIds)
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        foreach ($childes as $child) {
            $child->is_subscribed = 0;
        }

        return response()->json(response_formatter(DEFAULT_200, $childes), 200);
    }
}
