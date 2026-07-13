<?php

namespace Modules\BookingModule\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\AppCustomRequest;
use Modules\BookingModule\Entities\AppCustomRequestMessage;
use Modules\BookingModule\Services\AppCustomRequestSubmissionService;
use Modules\CategoryManagement\Entities\Category;
use Throwable;

class AppCustomRequestController extends Controller
{
    public function submit(Request $request, AppCustomRequestSubmissionService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'category_id' => 'nullable|string|max:36',
            'category_name' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ]);

        $validator->after(function ($validator) use ($request) {
            $categoryId = $request->input('category_id');
            if ($categoryId === null || $categoryId === '' || $categoryId === 'other') {
                return;
            }
            if (! Str::isUuid($categoryId) || ! Category::query()->where('id', $categoryId)->exists()) {
                $validator->errors()->add('category_id', translate('Invalid_category_selected'));
            }
        });

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 422);
        }

        $categoryId = $request->input('category_id');
        if ($categoryId === null || $categoryId === '' || $categoryId === 'other') {
            $categoryId = null;
        }

        try {
            $customRequest = $service->submit([
                'customer_id' => $request->user()?->id,
                'name' => trim((string) $request->input('name')),
                'phone' => trim((string) $request->input('phone')),
                'category_id' => $categoryId,
                'category_name' => trim((string) $request->input('category_name')),
                'description' => trim((string) $request->input('description')),
            ]);
        } catch (Throwable $e) {
            report($e);

            $message = translate('Something_went_wrong');
            if (str_contains($e->getMessage(), 'app_custom_requests')) {
                $message = 'Custom request storage is not ready on the server. Please run database migrations.';
            }

            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'app_custom_request_submit_failed', 'message' => $message],
            ]), 500);
        }

        return response()->json(response_formatter(DEFAULT_STORE_200, [
            'id' => $customRequest->id,
            'reference_id' => $customRequest->reference_id,
            'lead_id' => $customRequest->lead_id,
        ]));
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 422);
        }

        $requests = AppCustomRequest::query()
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->where('customer_id', $request->user()->id)
            ->latest('id')
            ->paginate((int) $request->input('limit'), ['*'], 'offset', (int) $request->input('offset'))
            ->withPath('');

        $requests->getCollection()->transform(fn (AppCustomRequest $item) => $this->formatSummary($item));

        if ($requests->count() > 0) {
            return response()->json(response_formatter(DEFAULT_200, $requests), 200);
        }

        return response()->json(response_formatter(DEFAULT_204, $requests), 200);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $customRequest = AppCustomRequest::query()
            ->with(['messages.sender'])
            ->where('customer_id', $request->user()->id)
            ->find($id);

        if (! $customRequest) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        return response()->json(response_formatter(DEFAULT_200, $this->formatDetail($customRequest)), 200);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 422);
        }

        $customRequest = AppCustomRequest::query()
            ->where('customer_id', $request->user()->id)
            ->find($id);

        if (! $customRequest) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        AppCustomRequestMessage::create([
            'app_custom_request_id' => $customRequest->id,
            'sender_type' => AppCustomRequestMessage::SENDER_CUSTOMER,
            'sender_id' => $request->user()->id,
            'message' => trim((string) $request->input('message')),
        ]);

        $customRequest->load(['messages.sender']);

        return response()->json(response_formatter(DEFAULT_STORE_200, $this->formatDetail($customRequest)), 200);
    }

    protected function formatSummary(AppCustomRequest $customRequest): array
    {
        $latestMessage = $customRequest->messages->first();

        return [
            'id' => $customRequest->id,
            'reference_id' => $customRequest->reference_id,
            'category_name' => $customRequest->category_name,
            'description' => $customRequest->description,
            'status' => $customRequest->status,
            'created_at' => $customRequest->created_at?->toIso8601String(),
            'latest_message' => $latestMessage?->message,
            'latest_message_sender_type' => $latestMessage?->sender_type,
            'latest_message_at' => $latestMessage?->created_at?->toIso8601String(),
        ];
    }

    protected function formatDetail(AppCustomRequest $customRequest): array
    {
        return [
            'id' => $customRequest->id,
            'reference_id' => $customRequest->reference_id,
            'name' => $customRequest->name,
            'phone' => $customRequest->phone,
            'category_name' => $customRequest->category_name,
            'description' => $customRequest->description,
            'status' => $customRequest->status,
            'created_at' => $customRequest->created_at?->toIso8601String(),
            'messages' => $customRequest->messages->map(fn (AppCustomRequestMessage $message) => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'message' => $message->message,
                'created_at' => $message->created_at?->toIso8601String(),
                'sender_name' => $message->sender_type === AppCustomRequestMessage::SENDER_ADMIN
                    ? translate('Admin')
                    : ($message->sender?->first_name ? trim($message->sender->first_name . ' ' . ($message->sender->last_name ?? '')) : $customRequest->name),
            ])->values(),
        ];
    }
}
