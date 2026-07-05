<?php

namespace Modules\InAppCallModule\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\InAppCallModule\Entities\InAppCall;
use function response;
use function response_formatter;

class InAppCallLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'channel_id' => 'nullable|uuid',
            'status' => 'nullable|string|max:32',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $query = InAppCall::query()
            ->with(['caller:id,first_name,last_name,phone,user_type', 'callee:id,first_name,last_name,phone,user_type'])
            ->when($request->filled('channel_id'), fn ($q) => $q->where('channel_id', $request->input('channel_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('created_at');

        $paginator = $query->paginate(
            (int) $request->input('limit'),
            ['*'],
            'offset',
            (int) $request->input('offset')
        )->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $paginator), 200);
    }
}
