<?php

namespace Modules\ServiceManagement\Http\Controllers\Web\Admin;

use App\Traits\UploadSizeHelperTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ServiceManagement\Support\ServiceOverviewIconPresets;
use Modules\ZoneManagement\Entities\Zone;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ServiceVariantController extends Controller
{
    use AuthorizesRequests;
    use UploadSizeHelperTrait;

    public function __construct(
        private Service $service,
        private ServiceVariant $serviceVariant,
        private Variation $variation,
        private Zone $zone,
    ) {
    }

    public function index(string $serviceId): View|Factory|RedirectResponse|Application
    {
        $this->authorize('service_view');
        $service = $this->service->with(['serviceVariants.zonePrices', 'variations'])->find($serviceId);
        if (! $service) {
            Toastr::error(translate(DEFAULT_204['message']));

            return back();
        }

        return view('servicemanagement::admin.variants.index', compact('service'));
    }

    public function panel(string $serviceId): View|Factory|JsonResponse|RedirectResponse|Application
    {
        $this->authorize('service_view');
        $service = $this->service->with(['serviceVariants.zonePrices', 'variations'])->find($serviceId);
        if (! $service) {
            if ($this->wantsVariationsPanel()) {
                return response()->json(['success' => false, 'message' => translate(DEFAULT_204['message'])], 404);
            }
            Toastr::error(translate(DEFAULT_204['message']));

            return back();
        }

        if ($this->wantsVariationsPanel()) {
            return $this->panelListResponse($service);
        }

        return redirect()->route('admin.service.edit', ['id' => $serviceId, 'tab' => 'variations']);
    }

    public function create(string $serviceId): View|Factory|RedirectResponse|Application
    {
        $this->authorize('service_update');
        $service = $this->service->find($serviceId);
        if (! $service) {
            Toastr::error(translate(DEFAULT_204['message']));

            return back();
        }

        $category = $service->category()->with(['zones'])->first();
        $zones = $this->resolveServiceZones($category);

        if ($this->wantsVariationsPanel()) {
            return view('servicemanagement::admin.partials._variant-panel-form', compact('service', 'zones'));
        }

        return view('servicemanagement::admin.variants.create', compact('service', 'zones'));
    }

    public function store(Request $request, string $serviceId): RedirectResponse|JsonResponse
    {
        $this->authorize('service_update');

        $check = $this->validateUploadedFile($request, ['image']);
        if ($check !== true) {
            return $this->wantsVariationsPanel() ? response()->json(['success' => false, 'message' => translate('validation_error')], 422) : $check;
        }

        $service = $this->service->find($serviceId);
        if (! $service) {
            if ($this->wantsVariationsPanel()) {
                return response()->json(['success' => false, 'message' => translate(DEFAULT_204['message'])], 404);
            }
            Toastr::error(translate(DEFAULT_204['message']));

            return back();
        }

        $request->validate([
            'title' => 'required|max:191',
            'description' => 'nullable|string|max:5000',
            'note' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:64|in:'.implode(',', ServiceOverviewIconPresets::keys()),
            'default_price' => 'required|numeric|min:0.01',
            'image' => 'nullable|image|max:'.uploadMaxFileSizeInKB('image').'|mimes:'.implode(',', array_column(IMAGEEXTENSION, 'key')),
        ]);

        $variantKey = str_replace(' ', '-', $request->title);
        if ($this->serviceVariant->where('service_id', $serviceId)->where('variant_key', $variantKey)->exists()) {
            if ($this->wantsVariationsPanel()) {
                return response()->json(['success' => false, 'message' => translate('already_exist')], 422);
            }
            Toastr::error(translate('already_exist'));

            return back()->withInput();
        }

        $variant = new ServiceVariant();
        $variant->service_id = $serviceId;
        $variant->variant_key = $variantKey;
        $variant->title = $request->title;
        $variant->description = $request->description;
        $variant->note = $request->note;
        $variant->icon = $request->filled('icon') ? $request->icon : null;
        $variant->sort_order = ((int) $this->serviceVariant->where('service_id', $serviceId)->max('sort_order')) + 1;
        $variant->is_active = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $variant->image = media_file_uploader(
                \App\Support\MediaStoragePath::serviceDir($service),
                'png',
                $request->file('image')
            );
        }

        $variant->save();
        $this->persistVariantPricing($request, $service, $variant);

        $message = translate(SERVICE_STORE_200['message']);
        if ($this->wantsVariationsPanel()) {
            return $this->panelJsonList($service->fresh(['serviceVariants.zonePrices', 'variations']), $message);
        }

        Toastr::success($message);

        return $this->variationsTabRedirect($serviceId);
    }

    public function show(string $serviceId, string $variantId): View|Factory|RedirectResponse|Application
    {
        $this->authorize('service_view');
        $service = $this->service->withoutGlobalScope('translate')->with(['variations'])->find($serviceId);
        $variant = $this->serviceVariant->withoutGlobalScope('translate')
            ->where('service_id', $serviceId)
            ->where('id', $variantId)
            ->with(['zonePrices', 'translations'])
            ->first();

        if (! $service || ! $variant) {
            Toastr::error(translate(DEFAULT_204['message']));

            return back();
        }

        $variant->setRelation('zonePrices', $variant->liveVariationRows($service));
        $category = $service->category()->with(['zones'])->first();
        $zones = $this->resolveServiceZones($category);
        [$zonePricingOn, $defaultPrice] = $variant->resolveAdminPricing($service);

        if ($this->wantsVariationsPanel()) {
            return view('servicemanagement::admin.partials._variant-panel-view', compact(
                'service',
                'variant',
                'zones',
                'zonePricingOn',
                'defaultPrice'
            ));
        }

        return view('servicemanagement::admin.variants.show', compact(
            'service',
            'variant',
            'zones',
            'zonePricingOn',
            'defaultPrice'
        ));
    }

    public function edit(string $serviceId, string $variantId): View|Factory|RedirectResponse|Application
    {
        $this->authorize('service_update');
        $service = $this->service->withoutGlobalScope('translate')->with(['variations'])->find($serviceId);
        $variant = $this->serviceVariant->withoutGlobalScope('translate')
            ->where('service_id', $serviceId)
            ->where('id', $variantId)
            ->with(['zonePrices', 'translations'])
            ->first();

        if (! $service || ! $variant) {
            Toastr::error(translate(DEFAULT_204['message']));

            return back();
        }

        $variant->setRelation('zonePrices', $variant->liveVariationRows($service));
        $category = $service->category()->with(['zones'])->first();
        $zones = $this->resolveServiceZones($category);
        [$zonePricingOn, $defaultPrice] = $variant->resolveAdminPricing($service);

        if ($this->wantsVariationsPanel()) {
            return view('servicemanagement::admin.partials._variant-panel-form', compact(
                'service',
                'variant',
                'zones',
                'zonePricingOn',
                'defaultPrice'
            ));
        }

        return view('servicemanagement::admin.variants.edit', compact(
            'service',
            'variant',
            'zones',
            'zonePricingOn',
            'defaultPrice'
        ));
    }

    public function update(Request $request, string $serviceId, string $variantId): RedirectResponse|JsonResponse
    {
        $this->authorize('service_update');

        $check = $this->validateUploadedFile($request, ['image']);
        if ($check !== true) {
            return $this->wantsVariationsPanel() ? response()->json(['success' => false, 'message' => translate('validation_error')], 422) : $check;
        }

        $service = $this->service->find($serviceId);
        $variant = $this->serviceVariant->where('service_id', $serviceId)->where('id', $variantId)->first();
        if (! $service || ! $variant) {
            if ($this->wantsVariationsPanel()) {
                return response()->json(['success' => false, 'message' => translate(DEFAULT_204['message'])], 404);
            }
            Toastr::error(translate(DEFAULT_204['message']));

            return back();
        }

        $request->validate([
            'title' => 'required|max:191',
            'description' => 'nullable|string|max:5000',
            'note' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:64|in:'.implode(',', ServiceOverviewIconPresets::keys()),
            'default_price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:'.uploadMaxFileSizeInKB('image').'|mimes:'.implode(',', array_column(IMAGEEXTENSION, 'key')),
        ]);

        $variant->title = $request->title;
        $variant->description = $request->description;
        $variant->note = $request->note;
        $variant->icon = $request->filled('icon') ? $request->icon : null;
        $variant->is_active = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $variant->image = media_file_uploader(
                \App\Support\MediaStoragePath::serviceDir($service),
                'png',
                $request->file('image'),
                $variant->image
            );
        }

        $variant->save();

        $this->persistVariantTranslations($request, $variant);
        $this->persistVariantPricing($request, $service, $variant);

        $message = translate(DEFAULT_UPDATE_200['message']);
        if ($this->wantsVariationsPanel()) {
            return $this->panelJsonList($service->fresh(['serviceVariants.zonePrices', 'variations']), $message);
        }

        Toastr::success($message);

        return $this->variationsTabRedirect($serviceId);
    }

    public function destroy(string $serviceId, string $variantId): RedirectResponse|JsonResponse
    {
        $this->authorize('service_update');
        $variant = $this->serviceVariant->where('service_id', $serviceId)->where('id', $variantId)->first();
        if (! $variant) {
            if ($this->wantsVariationsPanel()) {
                return response()->json(['success' => false, 'message' => translate(DEFAULT_204['message'])], 404);
            }
            Toastr::error(translate(DEFAULT_204['message']));

            return back();
        }

        $service = $this->service->find($serviceId);
        if ($service && is_array($service->variation_pricing)) {
            $vp = $service->variation_pricing;
            unset($vp[$variant->variant_key]);
            $service->variation_pricing = $vp;
            $service->save();
        }

        $this->variation->where('service_id', $serviceId)->where('variant_key', $variant->variant_key)->delete();
        $variant->delete();

        $message = translate(DEFAULT_DELETE_200['message']);
        if ($this->wantsVariationsPanel()) {
            $service = $this->service->with(['serviceVariants.zonePrices', 'variations'])->find($serviceId);

            return $this->panelJsonList($service, $message);
        }

        Toastr::success($message);

        return $this->variationsTabRedirect($serviceId);
    }

    private function wantsVariationsPanel(): bool
    {
        return request()->boolean('panel')
            || request()->header('X-Variations-Panel') === '1'
            || request()->expectsJson();
    }

    private function panelListResponse(Service $service): View|Factory|Application
    {
        return view('servicemanagement::admin.partials._variants-panel-list', compact('service'));
    }

    private function panelJsonList(Service $service, ?string $message = null): JsonResponse
    {
        $html = view('servicemanagement::admin.partials._variants-panel-list', compact('service'))->render();

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => $html,
        ]);
    }

    private function variationsTabRedirect(string $serviceId): RedirectResponse
    {
        return redirect()
            ->route('admin.service.edit', ['id' => $serviceId, 'tab' => 'variations'])
            ->with('service_updated', translate(DEFAULT_UPDATE_200['message']));
    }

    private function resolveServiceZones($category)
    {
        $zones = $category?->zones ?? collect();
        if ($zones->isEmpty()) {
            $zones = $this->zone->ofStatus(1)->latest()->get();
        }

        return $zones;
    }

    private function persistVariantPricing(Request $request, Service $service, ServiceVariant $variant): void
    {
        $category = $service->category()->with(['zones'])->first();
        $zones = $this->resolveServiceZones($category);
        if ($zones->isEmpty()) {
            $zones = $this->zone->latest()->get();
        }

        $useZone = $request->boolean('variant_use_zone_pricing');
        $defaultPrice = (float) $request->default_price;

        $rows = [];
        $writtenPrices = [];
        foreach ($zones as $zone) {
            $raw = $request->input($variant->variant_key.'_'.$zone->id.'_price');
            if ($raw === null) {
                $raw = $request->input('zone_prices.'.$zone->id);
            }
            if ($useZone) {
                $price = ($raw !== null && $raw !== '') ? (float) $raw : $defaultPrice;
            } else {
                $price = $defaultPrice;
            }
            $writtenPrices[] = round($price, 4);
            $rows[] = [
                'variant' => $variant->title,
                'variant_key' => $variant->variant_key,
                'service_variant_id' => $variant->id,
                'zone_id' => $zone->id,
                'price' => $price,
                'service_id' => $service->id,
            ];
        }

        // Keep JSON default aligned when every zone row shares one price.
        $uniqueWritten = collect($writtenPrices)->unique()->values();
        if ($uniqueWritten->count() === 1) {
            $defaultPrice = (float) $uniqueWritten->first();
        }

        $vp = is_array($service->variation_pricing) ? $service->variation_pricing : [];
        $vp[$variant->variant_key] = [
            'use_zone_pricing' => $useZone,
            'default_price' => $defaultPrice,
        ];
        $service->variation_pricing = $vp;
        $service->save();

        Variation::withoutGlobalScopes()
            ->where('service_id', $service->id)
            ->where('variant_key', $variant->variant_key)
            ->delete();

        if ($rows !== []) {
            $service->variations()->createMany($rows);
        }
    }

    private function persistVariantTranslations(Request $request, ServiceVariant $variant): void
    {
        if (! $request->has('lang') || ! is_array($request->lang)) {
            return;
        }

        $defaultLang = str_replace('_', '-', app()->getLocale());

        foreach ($request->lang as $index => $key) {
            if ($key === 'default') {
                continue;
            }

            foreach (['title', 'description', 'note'] as $field) {
                $values = $request->input($field);
                if (! is_array($values) || ! isset($values[$index])) {
                    continue;
                }

                $value = $values[$index];
                if ($defaultLang === $key && empty($value)) {
                    $value = $variant->{$field};
                }

                if (empty($value)) {
                    continue;
                }

                Translation::updateOrInsert(
                    [
                        'translationable_type' => ServiceVariant::class,
                        'translationable_id' => $variant->id,
                        'locale' => $key,
                        'key' => $field,
                    ],
                    ['value' => $value]
                );
            }
        }
    }
}
