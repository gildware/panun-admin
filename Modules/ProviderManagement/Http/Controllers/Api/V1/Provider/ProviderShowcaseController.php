<?php

namespace Modules\ProviderManagement\Http\Controllers\Api\V1\Provider;

use App\Traits\UploadSizeHelperTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\ProviderManagement\Entities\ProviderShowcaseItem;

class ProviderShowcaseController extends Controller
{
    use UploadSizeHelperTrait;

    private const MAX_ITEMS = 30;

    private ProviderShowcaseItem $showcaseItem;

    public function __construct(ProviderShowcaseItem $showcaseItem)
    {
        $this->showcaseItem = $showcaseItem;
    }

    private function providerId(): ?string
    {
        return auth('api')->user()?->provider?->id;
    }

    public function index(Request $request): JsonResponse
    {
        $providerId = $this->providerId();
        if (!$providerId) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $query = $this->showcaseItem->where('provider_id', $providerId);

        $status = $request->query('approval_status');
        if ($status === 'pending') {
            $query->where('is_approved', ProviderShowcaseItem::STATUS_PENDING);
        } elseif ($status === 'approved') {
            $query->where('is_approved', ProviderShowcaseItem::STATUS_APPROVED);
        } elseif ($status === 'denied') {
            $query->where('is_approved', ProviderShowcaseItem::STATUS_DENIED);
        }

        $items = $query
            ->with('storage')
            ->orderByDesc('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(response_formatter(DEFAULT_200, $items), 200);
    }

    public function store(Request $request): JsonResponse
    {
        $providerId = $this->providerId();
        if (!$providerId) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $check = $this->validateUploadedFile($request, ['media'], $request->input('media_type') === 'video' ? 'file' : 'image');
        if ($check !== true) {
            return $check;
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'media_type' => 'required|in:image,video',
            'media' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        if ($this->showcaseItem->where('provider_id', $providerId)->count() >= self::MAX_ITEMS) {
            return response()->json(response_formatter(DEFAULT_400, null, [['error_code' => 'showcase_limit', 'message' => translate('Maximum showcase items reached')]]), 400);
        }

        $mediaType = $request->input('media_type');
        $mediaRules = $mediaType === 'video'
            ? 'max:' . uploadMaxFileSizeInKB('file') . '|mimes:' . implode(',', array_column(VIDEO_EXTENSIONS, 'key'))
            : 'image|max:' . uploadMaxFileSizeInKB('image') . '|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key'));

        $mediaValidator = Validator::make($request->all(), ['media' => $mediaRules]);
        if ($mediaValidator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($mediaValidator)), 400);
        }

        $provider = auth('api')->user()?->provider;
        $file = $request->file('media');
        $extension = $file->getClientOriginalExtension();
        $fileName = $provider
            ? media_file_uploader(\App\Support\MediaStoragePath::providerSectionDir($provider, 'showcase'), $extension, $file)
            : file_uploader('provider/showcase/', $extension, $file);

        $item = $this->showcaseItem->create([
            'provider_id' => $providerId,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'media_type' => $mediaType,
            'file_name' => $fileName,
            'sort_order' => (int) ($request->input('sort_order') ?? 0),
            'is_active' => 1,
            'is_approved' => ProviderShowcaseItem::STATUS_PENDING,
        ]);

        admin_inbox_notify_showcase_submitted($item);
        send_showcase_provider_notification($item, 'showcase_submitted');

        return response()->json(response_formatter(DEFAULT_STORE_200, $item->load('storage')), 200);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $providerId = $this->providerId();
        if (!$providerId) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $item = $this->showcaseItem->where('provider_id', $providerId)->find($id);
        if (!$item) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $provider = auth('api')->user()?->provider;

        if ($request->hasFile('media')) {
            $fileType = $request->input('media_type', $item->media_type) === 'video' ? 'file' : 'image';
            $check = $this->validateUploadedFile($request, ['media'], $fileType);
            if ($check !== true) {
                return $check;
            }
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'media_type' => 'nullable|in:image,video',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $wasApproved = $item->is_approved === ProviderShowcaseItem::STATUS_APPROVED;

        DB::transaction(function () use ($request, $item, $provider) {
            if ($request->has('title')) {
                $item->title = $request->input('title');
            }
            if ($request->has('description')) {
                $item->description = $request->input('description');
            }
            if ($request->has('media_type')) {
                $item->media_type = $request->input('media_type');
            }
            if ($request->has('sort_order')) {
                $item->sort_order = (int) $request->input('sort_order');
            }
            if ($request->has('is_active')) {
                $item->is_active = (int) $request->input('is_active');
            }

            if ($request->hasFile('media')) {
                $mediaType = $request->input('media_type', $item->media_type);
                $file = $request->file('media');
                $extension = $file->getClientOriginalExtension();
                $item->file_name = $provider
                    ? media_file_uploader(
                        \App\Support\MediaStoragePath::providerSectionDir($provider, 'showcase'),
                        $extension,
                        $file,
                        $item->file_name
                    )
                    : file_uploader('provider/showcase/', $extension, $file, $item->file_name);
                $item->media_type = $mediaType;
            }

            if ($item->is_approved === ProviderShowcaseItem::STATUS_APPROVED) {
                $item->is_approved = ProviderShowcaseItem::STATUS_PENDING;
            }

            $item->save();
        });

        $item = $item->fresh()->load('storage');
        if ($wasApproved && $item->is_approved === ProviderShowcaseItem::STATUS_PENDING) {
            admin_inbox_notify_showcase_submitted($item);
            send_showcase_provider_notification($item, 'showcase_submitted');
        }

        return response()->json(response_formatter(DEFAULT_UPDATE_200, $item), 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $providerId = $this->providerId();
        if (!$providerId) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $item = $this->showcaseItem->where('provider_id', $providerId)->find($id);
        if (!$item) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $item->delete();

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }
}
