<?php

namespace Modules\ZoneManagement\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\ZoneManagement\Entities\Zone;
use Modules\ZoneManagement\Services\ZoneTreeBuilder;

class PublicZoneController extends Controller
{

    private Zone $zone;

    public function __construct(Zone $zone)
    {
        $this->zone = $zone;
    }

    /**
     * Show the specified resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'string' => 'string',
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $zones = $this->zone
            ->ofStatus(1)
            ->withCount(['providers'])
            ->when($request->has('string'), function ($query) use ($request) {
                $keys = explode(' ', base64_decode($request['string']));
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

            if (count($zones) > 0) {
                return response()->json(response_formatter(DEFAULT_200, $zones), 200);
            }

        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    /**
     * Nested zone tree for provider registration / selection UIs.
     */
    public function tree(Request $request): JsonResponse
    {
        $forRegistration = $request->boolean('for_registration')
            || $request->boolean('has_categories');

        $nodes = app(ZoneTreeBuilder::class)->buildActiveZoneTree($forRegistration);

        return response()->json(response_formatter(DEFAULT_200, ['nodes' => $nodes]), 200);
    }
}
