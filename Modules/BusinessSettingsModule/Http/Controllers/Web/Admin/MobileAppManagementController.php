<?php

namespace Modules\BusinessSettingsModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\BusinessSettingsModule\Entities\MobileAppAiMessage;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Services\MobileAppAiSettingsService;
use Modules\BusinessSettingsModule\Services\MobileAppManagementService;
use Modules\CustomerModule\Services\CustomerApiResponseCache;
use Modules\CategoryManagement\Entities\Category;
use Modules\PromotionManagement\Entities\Banner;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Illuminate\Http\JsonResponse;

class MobileAppManagementController extends Controller
{
    use AuthorizesRequests;

    public const AI_TABS = ['ai_config', 'ai_chat'];

    public const ICON_TABS = ['logos', 'customer', 'provider'];

    public function __construct(
        protected MobileAppAiSettingsService $settingsService,
        protected MobileAppManagementService $managementService,
    ) {}

    public function ai(Request $request): View
    {
        $this->authorize('mobile_app_ai_view');

        $tab = $this->normalizeAiTab($request->query('tab'));
        $settings = $this->settingsService->settings();

        $conversations = null;
        $selectedConversation = null;
        $messages = null;

        if ($tab === 'ai_chat') {
            $conversations = MobileAppAiConversation::query()
                ->withInAppAiChats()
                ->with([
                    'user:id,first_name,last_name,email,phone',
                    'appMessages' => fn ($q) => $q->orderByDesc('id')->limit(1),
                ])
                ->withCount([
                    'appMessages as app_message_count',
                    'appMessages as customer_message_count' => fn ($q) => $q->where('role', 'user'),
                ])
                ->orderByDesc('last_message_at')
                ->paginate(20)
                ->withQueryString();

            $conversationId = $request->query('conversation_id');
            if ($conversationId) {
                $selectedConversation = MobileAppAiConversation::query()
                    ->withInAppAiChats()
                    ->with(['user:id,first_name,last_name,email,phone'])
                    ->find($conversationId);
                if ($selectedConversation) {
                    $messages = MobileAppAiMessage::query()
                        ->where('conversation_id', $selectedConversation->id)
                        ->where('source', MobileAppAiMessage::SOURCE_MOBILE_APP)
                        ->orderBy('id')
                        ->get();
                }
            }
        }

        return view('businesssettingsmodule::admin.mobile-app-management.ai', [
            'tab' => $tab,
            'tabs' => [
                ['id' => 'ai_config', 'label' => translate('AI_Config')],
                ['id' => 'ai_chat', 'label' => translate('AI_Chat')],
            ],
            'settings' => $settings,
            'resolvedPromptPreview' => mb_substr($this->settingsService->resolvedSystemPrompt(), 0, 2000),
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'messages' => $messages,
        ]);
    }

    public function updateAiConfig(Request $request): RedirectResponse
    {
        $this->authorize('mobile_app_ai_update');

        $validator = Validator::make($request->all(), [
            'is_enabled' => 'nullable|in:0,1',
            'inherit_whatsapp_ai' => 'nullable|in:0,1',
            'use_full_custom_prompt' => 'nullable|in:0,1',
            'gemini_model' => 'nullable|string|max:120',
            'max_history_messages' => 'nullable|integer|min:6|max:60',
            'assistant_persona' => 'nullable|string|max:65000',
            'prompt_addendum' => 'nullable|string|max:65000',
            'custom_system_prompt' => 'nullable|string|max:65000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $row = $this->settingsService->settings();
        $row->fill([
            'is_enabled' => $request->boolean('is_enabled'),
            'inherit_whatsapp_ai' => $request->boolean('inherit_whatsapp_ai'),
            'use_full_custom_prompt' => $request->boolean('use_full_custom_prompt'),
            'gemini_model' => $request->input('gemini_model'),
            'max_history_messages' => (int) $request->input('max_history_messages', 24),
            'assistant_persona' => $request->input('assistant_persona'),
            'prompt_addendum' => $request->input('prompt_addendum'),
            'custom_system_prompt' => $request->input('custom_system_prompt'),
        ]);
        $row->save();

        Toastr::success(translate('settings_updated'));

        return redirect()->route('admin.mobile-app-management.ai', ['tab' => 'ai_config']);
    }

    public function homePage(): View
    {
        $this->authorize('mobile_app_home_page_view');

        $sections = $this->managementService->getHomeSections();
        $previewPayload = $this->managementService->buildHomePagePreviewData();

        return view('businesssettingsmodule::admin.mobile-app-management.home-page', [
            'sections' => $sections,
            'picklists' => $this->managementService->resolvePicklistLabels(),
            'previewPayload' => $previewPayload,
            'businessName' => (business_config('business_name', 'business_information'))?->live_values ?? 'Your Business',
            'businessLogo' => getBusinessSettingsImageFullPath(
                key: 'business_logo',
                settingType: 'business_information',
                path: 'business/',
                defaultPath: 'assets/admin-module/img/media/banner-upload-file.png',
            ),
            'configFlags' => [
                'direct_provider_booking' => (int) ((business_config('direct_provider_booking', 'business_information'))?->live_values ?? 0),
                'bidding_status' => (int) ((business_config('bidding_status', 'bidding_system'))?->live_values ?? 0),
            ],
        ]);
    }

    public function searchServices(Request $request): JsonResponse
    {
        $this->authorize('mobile_app_home_page_view');

        $q = trim((string) $request->query('q', ''));
        $query = Service::query()->active()->ofStatus(1)->select(['id', 'name']);

        if ($q !== '') {
            $query->where('name', 'like', '%'.$q.'%');
        }

        $results = $query->orderBy('name')->limit(30)->get()->map(fn ($s) => [
            'id' => $s->id,
            'text' => $s->name,
            'image' => $s->thumbnail_full_path ?: $s->cover_image_full_path ?: '',
        ]);

        return response()->json(['results' => $results]);
    }

    public function searchProviders(Request $request): JsonResponse
    {
        $this->authorize('mobile_app_home_page_view');

        $q = trim((string) $request->query('q', ''));
        $query = Provider::query()->ofStatus(1)->select(['id', 'company_name']);

        if ($q !== '') {
            $query->where('company_name', 'like', '%'.$q.'%');
        }

        $results = $query->orderBy('company_name')->limit(30)->get()->map(fn ($p) => [
            'id' => $p->id,
            'text' => $p->company_name,
            'image' => $p->logo_full_path ?: $p->cover_image_full_path ?: '',
        ]);

        return response()->json(['results' => $results]);
    }

    public function searchBanners(Request $request): JsonResponse
    {
        $this->authorize('mobile_app_home_page_view');

        $q = trim((string) $request->query('q', ''));
        $query = Banner::query()->ofStatus(1);

        if ($q !== '') {
            $query->where('banner_title', 'like', '%'.$q.'%');
        }

        $results = $query->orderByDesc('id')->limit(30)->get()->map(fn ($b) => [
            'id' => $b->id,
            'text' => $b->banner_title ?: ('Banner #'.substr($b->id, 0, 8)),
            'image' => $b->banner_image_full_path ?? '',
        ]);

        return response()->json(['results' => $results]);
    }

    public function searchCampaigns(Request $request): JsonResponse
    {
        $this->authorize('mobile_app_home_page_view');

        return response()->json([
            'results' => $this->managementService->searchCampaignsForPicker(
                trim((string) $request->query('q', '')),
                30
            ),
        ]);
    }

    public function searchCategories(Request $request): JsonResponse
    {
        $this->authorize('mobile_app_home_page_view');

        return response()->json([
            'results' => $this->managementService->searchCategoriesForPicker(
                trim((string) $request->query('q', '')),
                30
            ),
        ]);
    }

    public function searchSubCategories(Request $request): JsonResponse
    {
        $this->authorize('mobile_app_home_page_view');

        return response()->json([
            'results' => $this->managementService->searchSubCategoriesForPicker(
                trim((string) $request->query('q', '')),
                30
            ),
        ]);
    }

    public function updateHomePage(Request $request): RedirectResponse
    {
        $this->authorize('mobile_app_home_page_update');

        $sectionsInput = $this->normalizeHomePageSectionsInput($request->input('sections', []));
        $request->merge(['sections' => $sectionsInput]);

        $validator = Validator::make($request->all(), [
            'sections' => 'required|array|min:1',
            'sections.*.key' => 'required|string|max:80',
            'sections.*.enabled' => 'nullable|in:0,1',
            'sections.*.sort_order' => 'nullable|integer|min:0|max:99',
            'sections.*.title' => 'nullable|string|max:120',
            'sections.*.item_limit' => 'nullable|integer|min:1|max:50',
            'sections.*.data_mode' => 'nullable|in:default,manual',
            'sections.*.content_type' => 'nullable|in:services,providers,banners,categories,sub_categories,campaigns',
            'sections.*.service_ids' => 'nullable|array',
            'sections.*.service_ids.*' => 'uuid',
            'sections.*.provider_ids' => 'nullable|array',
            'sections.*.provider_ids.*' => 'uuid',
            'sections.*.banner_ids' => 'nullable|array',
            'sections.*.banner_ids.*' => 'uuid',
            'sections.*.campaign_ids' => 'nullable|array',
            'sections.*.campaign_ids.*' => 'uuid',
            'sections.*.category_ids' => 'nullable|array',
            'sections.*.category_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $payload = [];
        foreach ($sectionsInput as $row) {
            $payload[] = [
                'key' => $row['key'],
                'enabled' => isset($row['enabled']) && (string) $row['enabled'] === '1',
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'title' => $row['title'] ?? null,
                'item_limit' => $row['item_limit'] ?? null,
                'data_mode' => $row['data_mode'] ?? 'default',
                'content_type' => $row['content_type'] ?? null,
                'service_ids' => $row['service_ids'] ?? [],
                'provider_ids' => $row['provider_ids'] ?? [],
                'banner_ids' => $row['banner_ids'] ?? [],
                'campaign_ids' => $row['campaign_ids'] ?? [],
                'category_ids' => $row['category_ids'] ?? [],
            ];
        }

        $this->managementService->saveHomeSections($payload);
        Toastr::success(translate('settings_updated'));

        return redirect()->route('admin.mobile-app-management.home-page');
    }

    public function icons(Request $request): View
    {
        $this->authorize('mobile_app_icons_view');

        $tab = $this->normalizeIconTab($request->query('tab'));
        $groups = MobileAppManagementService::iconGroupDefinitions();
        $icons = $this->managementService->getIcons();

        $iconPreviews = ['customer' => [], 'provider' => []];
        foreach (['customer', 'provider'] as $app) {
            foreach ($icons[$app] ?? [] as $key => $variants) {
                $iconPreviews[$app][$key] = [
                    'light' => $this->managementService->iconFullPath($variants['light'] ?? null),
                    'dark' => $this->managementService->iconFullPath($variants['dark'] ?? null),
                ];
            }
        }

        return view('businesssettingsmodule::admin.mobile-app-management.icons', [
            'tab' => $tab,
            'tabs' => [
                ['id' => 'logos', 'label' => translate('Logos')],
                ['id' => 'customer', 'label' => translate('Customer_icons')],
                ['id' => 'provider', 'label' => translate('Provider_icons')],
            ],
            'tabIconItems' => $this->iconItemsForTab($tab, $groups),
            'icons' => $icons,
            'iconPreviews' => $iconPreviews,
            'iconVariants' => MobileAppManagementService::ICON_VARIANTS,
        ]);
    }

    public function updateIcons(Request $request): RedirectResponse
    {
        $this->authorize('mobile_app_icons_update');

        $allKeys = [];
        foreach (MobileAppManagementService::iconGroupDefinitions() as $group) {
            foreach (['customer', 'provider'] as $app) {
                foreach ($group[$app] ?? [] as $def) {
                    $allKeys[$app][] = $def['key'];
                }
            }
        }

        $stored = $this->managementService->getIcons();

        foreach (['customer', 'provider'] as $app) {
            foreach ($allKeys[$app] ?? [] as $key) {
                foreach (MobileAppManagementService::ICON_VARIANTS as $variant) {
                    $field = "icon_{$app}_{$key}_{$variant}";
                    if (!$request->hasFile($field)) {
                        continue;
                    }

                    $existing = $stored[$app][$key][$variant] ?? null;
                    if ($existing) {
                        file_remover('mobile-app/', $existing);
                    }

                    $filename = file_uploader('mobile-app/', APPLICATION_IMAGE_FORMAT, $request->file($field), $existing ?? '');

                    if (!$filename || $filename === 'def.png') {
                        continue;
                    }

                    $stored[$app][$key][$variant] = $filename;
                }
            }
        }

        $this->managementService->saveIcons($stored);
        Toastr::success(translate('settings_updated'));

        return redirect()->route('admin.mobile-app-management.icons', [
            'tab' => $this->normalizeIconTab($request->input('tab')),
        ]);
    }

    public function settings(): View
    {
        $this->authorize('mobile_app_home_page_view');

        return view('businesssettingsmodule::admin.mobile-app-management.settings', [
            'biddingStatus' => (int) ((business_config('bidding_status', 'bidding_system'))?->live_values ?? 0),
            'biddingPostValidity' => (int) ((business_config('bidding_post_validity', 'bidding_system'))?->live_values ?? 7),
            'bidOffersVisibility' => (int) ((business_config('bid_offers_visibility_for_providers', 'bidding_system'))?->live_values ?? 0),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->authorize('mobile_app_home_page_update');

        $validator = Validator::make($request->all(), [
            'bidding_status' => 'required|in:0,1',
            'bidding_post_validity' => 'required_if:bidding_status,1|nullable|integer|min:1|max:365',
            'bid_offers_visibility_for_providers' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $biddingEnabled = (string) $validated['bidding_status'] === '1';

        foreach ([
            'bidding_status' => $validated['bidding_status'],
            'bidding_post_validity' => $biddingEnabled
                ? ($validated['bidding_post_validity'] ?? 7)
                : ((business_config('bidding_post_validity', 'bidding_system'))?->live_values ?? 7),
            'bid_offers_visibility_for_providers' => $validated['bid_offers_visibility_for_providers'],
        ] as $key => $value) {
            BusinessSettings::query()->updateOrCreate(
                ['key_name' => $key],
                [
                    'key_name' => $key,
                    'live_values' => $value,
                    'test_values' => $value,
                    'settings_type' => 'bidding_system',
                    'mode' => 'live',
                    'is_active' => 1,
                ],
            );
        }

        CustomerApiResponseCache::forgetConfigCaches();
        Toastr::success(translate('settings_updated'));

        return redirect()->route('admin.mobile-app-management.settings');
    }

    /**
     * @param array<string, array<string, list<array{key: string, label: string}>>> $groups
     * @return list<array{appKey: string, def: array{key: string, label: string}}>
     */
    private function iconItemsForTab(string $tab, array $groups): array
    {
        if ($tab === 'logos') {
            $items = [];
            foreach (['customer', 'provider'] as $appKey) {
                foreach ($groups['logos'][$appKey] ?? [] as $def) {
                    $items[] = ['appKey' => $appKey, 'def' => $def];
                }
            }

            return $items;
        }

        if ($tab === 'customer') {
            return array_map(
                fn (array $def) => ['appKey' => 'customer', 'def' => $def],
                array_merge(
                    $groups['menu']['customer'] ?? [],
                    $groups['bottom_navigation']['customer'] ?? [],
                ),
            );
        }

        return array_map(
            fn (array $def) => ['appKey' => 'provider', 'def' => $def],
            array_merge(
                $groups['menu']['provider'] ?? [],
                $groups['bottom_navigation']['provider'] ?? [],
            ),
        );
    }

    private function normalizeIconTab(?string $tab): string
    {
        $tab = (string) $tab;
        if (in_array($tab, self::ICON_TABS, true)) {
            return $tab;
        }

        return 'logos';
    }

    private function normalizeAiTab(?string $tab): string
    {
        $tab = (string) $tab;
        if (in_array($tab, self::AI_TABS, true)) {
            return $tab;
        }

        return 'ai_config';
    }

    /**
     * @param mixed $sections
     * @return list<array<string, mixed>>
     */
    private function normalizeHomePageSectionsInput(mixed $sections): array
    {
        if (!is_array($sections)) {
            return [];
        }

        $normalized = [];
        foreach ($sections as $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach (['service_ids', 'provider_ids', 'banner_ids', 'campaign_ids', 'category_ids'] as $field) {
                $ids = $row[$field] ?? [];
                if (!is_array($ids)) {
                    $ids = $ids === null || $ids === '' ? [] : [$ids];
                }
                $row[$field] = array_values(array_filter(array_map(
                    static fn ($id) => trim((string) $id),
                    $ids
                ), static fn ($id) => $id !== '' && preg_match('/^[0-9a-f-]{36}$/i', $id)));
            }

            $dataMode = ($row['data_mode'] ?? 'default') === 'manual' ? 'manual' : 'default';
            $row['data_mode'] = $dataMode;
            if ($dataMode !== 'manual') {
                $row['service_ids'] = [];
                $row['provider_ids'] = [];
                $row['banner_ids'] = [];
                $row['campaign_ids'] = [];
                $row['category_ids'] = [];
            }

            $limit = $row['item_limit'] ?? null;
            if ($limit === '' || $limit === null) {
                unset($row['item_limit']);
            } elseif (is_numeric($limit)) {
                $row['item_limit'] = (int) $limit;
            }

            $normalized[] = $row;
        }

        return $normalized;
    }
}
