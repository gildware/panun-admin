<?php

namespace Modules\ServiceManagement\Http\Controllers\Web\Admin;

use App\Support\MediaStoragePath;
use App\Traits\UploadSizeHelperTrait;
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
  use UploadSizeHelperTrait;

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

  public function uploadImage(Request $request, string $serviceId): JsonResponse
  {
    $this->authorize('service_update');

    $service = Service::query()
      ->withoutGlobalScope('translate')
      ->select(['id', 'name', 'slug'])
      ->find($serviceId);

    if (! $service) {
      return response()->json(['flag' => 0, 'message' => translate(DEFAULT_204['message'])], 404);
    }

    $check = $this->validateUploadedFile($request, ['image']);
    if ($check !== true) {
      return response()->json(['flag' => 0, 'message' => translate('invalid_file')], 400);
    }

    $request->validate([
      'image' => 'required|image|max:'.uploadMaxFileSizeInKB('image').'|mimes:'.implode(',', array_column(IMAGEEXTENSION, 'key')),
      'old_url' => 'nullable|string|max:2048',
    ]);

    $oldKey = media_storage_key_from_url($request->input('old_url'));
    $storageKey = media_file_uploader(
      MediaStoragePath::serviceOverviewDir($service),
      'png',
      $request->file('image'),
      $oldKey
    );

    $url = resolve_media_storage_url($storageKey, '', null, null, false);

    return response()->json([
      'flag' => 1,
      'url' => $url,
      'key' => $storageKey,
      'message' => translate(DEFAULT_UPDATE_200['message']),
    ]);
  }

  public function update(Request $request, string $serviceId): JsonResponse
  {
    $this->authorize('service_update');
    $service = Service::query()
      ->withoutGlobalScope('translate')
      ->select(['id', 'overview_content'])
      ->find($serviceId);
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

    $existing = is_array($service->overview_content) ? $service->overview_content : [];
    if (! array_key_exists('card_highlights', $payload) && ! empty($existing['card_highlights'])) {
      $payload['card_highlights'] = $existing['card_highlights'];
    }

    $normalized = ServiceOverviewContentResolver::normalizeServiceContent($payload);
    $this->deleteOrphanedOverviewImages($existing, $normalized);
    $service->overview_content = $normalized;
    $service->saveQuietly();

    return response()->json([
      'flag' => 1,
      'message' => translate(DEFAULT_UPDATE_200['message']),
    ]);
  }

  /**
   * @param  array<string, mixed>  $previous
   * @param  array<string, mixed>  $next
   */
  private function deleteOrphanedOverviewImages(array $previous, array $next): void
  {
    $previousUrls = $this->collectOverviewImageUrls($previous);
    $nextUrls = $this->collectOverviewImageUrls($next);

    foreach (array_diff($previousUrls, $nextUrls) as $removedUrl) {
      $key = media_storage_key_from_url($removedUrl);
      if ($key !== null) {
        media_storage_delete($key);
      }
    }
  }

  /**
   * @param  array<string, mixed>  $content
   * @return list<string>
   */
  private function collectOverviewImageUrls(array $content): array
  {
    $urls = [];

    foreach (['service_process', 'perfect_for', 'whats_included', 'whats_not_included', 'good_to_know', 'terms_and_conditions', 'why_choose'] as $sectionKey) {
      $section = $content[$sectionKey] ?? null;
      if (! is_array($section)) {
        continue;
      }
      foreach ($section['items'] ?? [] as $item) {
        if (! is_array($item)) {
          continue;
        }
        foreach (['image', 'icon_image'] as $field) {
          $value = trim((string) ($item[$field] ?? ''));
          if ($value !== '') {
            $urls[] = $value;
          }
        }
      }
    }

    foreach (['top_icons', 'card_highlights'] as $listKey) {
      foreach ($content[$listKey] ?? [] as $item) {
        if (! is_array($item)) {
          continue;
        }
        foreach (['image', 'icon_image'] as $field) {
          $value = trim((string) ($item[$field] ?? ''));
          if ($value !== '') {
            $urls[] = $value;
          }
        }
      }
    }

    return array_values(array_unique($urls));
  }
}
