<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingTemplate;
use Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;

class WhatsAppBookingTemplateController extends Controller
{
    use AuthorizesRequests;

    /**
     * Main booking template UI tabs (suffix after wa-tpl-pane-).
     *
     * @return list<string>
     */
    private static function bookingTemplateMainTabKeys(): array
    {
        return [
            'new-booking',
            'status',
            'provider-change',
            'schedule',
            'payment',
            'ledger-payments',
            'serviceman',
            'verification',
        ];
    }

    public function edit(Request $request): View
    {
        $this->authorize('whatsapp_message_template_view');

        $service = app(BookingWhatsAppNotificationService::class);
        $config = $service->getConfig();
        $placeholders = BookingWhatsAppNotificationService::allPlaceholderHintsForAdmin();
        $statusTemplateSegments = BookingWhatsAppNotificationService::statusTemplateSegmentKeys();

        $allowedMain = self::bookingTemplateMainTabKeys();
        $waActiveMainTab = (string) old('wa_active_main_tab', $request->query('tab', 'new-booking'));
        if (! in_array($waActiveMainTab, $allowedMain, true)) {
            $waActiveMainTab = 'new-booking';
        }
        $waActiveStatusSegment = (string) old('wa_active_status_segment', $request->query('status', ''));
        if ($waActiveMainTab === 'status') {
            if ($waActiveStatusSegment === '' || ! in_array($waActiveStatusSegment, $statusTemplateSegments, true)) {
                $waActiveStatusSegment = $statusTemplateSegments[0] ?? '';
            }
        } else {
            $waActiveStatusSegment = '';
        }

        $waTemplates = WhatsAppMarketingTemplate::query()
            ->orderByRaw("CASE UPPER(COALESCE(status,'')) WHEN 'APPROVED' THEN 0 WHEN 'PENDING' THEN 1 WHEN 'REJECTED' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->orderBy('language')
            ->get();

        $waTemplatesJson = $waTemplates->map(static function ($t) {
            $components = is_array($t->components) ? $t->components : [];
            $bodyPlan = WhatsAppCloudService::resolveBodyParameterPlanFromTemplate(['components' => $components]);
            $headerPlan = WhatsAppCloudService::resolveHeaderTextParameterPlanFromTemplate(['components' => $components]);

            return [
                'id' => $t->id,
                'name' => $t->name,
                'language' => $t->language,
                'status' => $t->status,
                'category' => $t->category,
                'body_count' => (int) $t->body_parameter_count,
                'body_plan' => $bodyPlan,
                'header_plan' => $headerPlan,
                'preview_state' => WhatsAppCloudService::extractTemplatePreviewState($components),
                'components' => $components,
            ];
        })->values()->all();

        $placeholderHints = $placeholders;
        $placeholderGuides = BookingWhatsAppNotificationService::allPlaceholderAdminGuidesForAdmin();
        $placeholderDropdownModules = array_map(
            static fn (string $langKey) => translate($langKey),
            BookingWhatsAppNotificationService::allPlaceholderDropdownModuleLangKeysForAdmin()
        );
        $bookingTokenKeys = array_keys($placeholders);
        $placeholderSamples = BookingWhatsAppNotificationService::allPlaceholderPreviewSamplesForAdmin();

        return view('whatsappmodule::admin.booking-message-templates', compact(
            'config',
            'placeholders',
            'statusTemplateSegments',
            'waTemplates',
            'waTemplatesJson',
            'placeholderHints',
            'placeholderGuides',
            'placeholderDropdownModules',
            'placeholderSamples',
            'bookingTokenKeys',
            'waActiveMainTab',
            'waActiveStatusSegment'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        $service = app(BookingWhatsAppNotificationService::class);
        $segments = BookingWhatsAppNotificationService::statusTemplateSegmentKeys();
        $tokenKeys = array_keys(BookingWhatsAppNotificationService::allPlaceholderHintsForAdmin());
        $tokenRule = ['nullable', 'string', 'max:128', Rule::in(array_merge([''], $tokenKeys))];

        $rules = [];
        foreach (BookingWhatsAppNotificationService::configurableMessageKeys() as $msgKey) {
            $rules[$msgKey . '_wa_tpl_id'] = 'nullable|integer|exists:whatsapp_marketing_templates,id';
            $rules[$msgKey . '_wa_body_params'] = 'nullable|array|max:64';
            $rules[$msgKey . '_wa_body_params.*'] = $tokenRule;
            $rules[$msgKey . '_wa_header_params'] = 'nullable|array|max:32';
            $rules[$msgKey . '_wa_header_params.*'] = $tokenRule;
        }

        $this->dropStaleWaHeaderMappingsBeforeValidate($request);

        $data = $request->validate($rules);
        $post = $request->post();

        foreach (BookingWhatsAppNotificationService::configurableMessageKeys() as $msgKey) {
            $tid = $data[$msgKey . '_wa_tpl_id'] ?? null;
            if (!$tid) {
                continue;
            }
            $tpl = WhatsAppMarketingTemplate::query()->find((int) $tid);
            if (!$tpl || strtoupper((string) $tpl->status) !== 'APPROVED') {
                throw ValidationException::withMessages([
                    $msgKey . '_wa_tpl_id' => [translate('whatsapp_template_not_found_or_inactive')],
                ]);
            }
            $components = is_array($tpl->components) ? $tpl->components : [];
            $bodyPlan = WhatsAppCloudService::resolveBodyParameterPlanFromTemplate(['components' => $components]);
            $headerPlan = WhatsAppCloudService::resolveHeaderTextParameterPlanFromTemplate(['components' => $components]);

            $expectedBody = ($bodyPlan['format'] ?? '') === 'named'
                ? count($bodyPlan['named_param_names'] ?? [])
                : (int) ($bodyPlan['positional_count'] ?? 0);
            $expectedHeader = ($headerPlan['format'] ?? '') === 'named'
                ? count($headerPlan['named_param_names'] ?? [])
                : (int) ($headerPlan['positional_count'] ?? 0);

            $bpKey = $msgKey . '_wa_body_params';
            $hpKey = $msgKey . '_wa_header_params';
            $bodyParams = array_values(array_map(
                static fn ($v) => is_string($v) ? trim($v) : '',
                (array) ($data[$bpKey] ?? (is_array($post[$bpKey] ?? null) ? $post[$bpKey] : []))
            ));
            $headerParams = array_values(array_map(
                static fn ($v) => is_string($v) ? trim($v) : '',
                (array) ($data[$hpKey] ?? (is_array($post[$hpKey] ?? null) ? $post[$hpKey] : []))
            ));

            if (count($bodyParams) !== $expectedBody) {
                throw ValidationException::withMessages([
                    $msgKey . '_wa_body_params' => [translate('whatsapp_template_body_vars_wrong_count')],
                ]);
            }
            if (count($headerParams) !== $expectedHeader) {
                throw ValidationException::withMessages([
                    $msgKey . '_wa_header_params' => [translate('whatsapp_template_header_text_vars_wrong_count')],
                ]);
            }
        }

        $currentCfg = $service->getConfig();

        $liveValues = [
            'enabled' => (bool) ($currentCfg['enabled'] ?? false),
            'default_phone_prefix' => '91',
            'apply_default_phone_prefix' => true,
        ];

        foreach (BookingWhatsAppNotificationService::configurableMessageKeys() as $msgKey) {
            $liveValues[$msgKey] = '';
            $wk = $msgKey . '_wa_tpl_id';
            if (is_array($post) && array_key_exists($wk, $post)) {
                $raw = $post[$wk];
                $liveValues[$wk] = ($raw !== null && $raw !== '') ? (int) $raw : null;
            } else {
                $liveValues[$wk] = $currentCfg[$wk] ?? null;
            }
            // Wildcard rules (*_wa_body_params.*) omit the parent from validated(). Use raw POST so mappings persist.
            // Do not use $request->has() — it is unreliable for array fields in some cases.
            $bp = $msgKey . '_wa_body_params';
            $postedBody = is_array($post) ? ($post[$bp] ?? null) : null;
            if (is_array($postedBody)) {
                $liveValues[$bp] = array_values(array_map(
                    static fn ($v) => is_string($v) ? trim($v) : '',
                    $postedBody
                ));
            } else {
                $liveValues[$bp] = is_array($currentCfg[$bp] ?? null)
                    ? array_values($currentCfg[$bp])
                    : [];
            }
            $hp = $msgKey . '_wa_header_params';
            $postedHeader = is_array($post) ? ($post[$hp] ?? null) : null;
            if (is_array($postedHeader)) {
                $liveValues[$hp] = array_values(array_map(
                    static fn ($v) => is_string($v) ? trim($v) : '',
                    $postedHeader
                ));
            } else {
                $liveValues[$hp] = is_array($currentCfg[$hp] ?? null)
                    ? array_values($currentCfg[$hp])
                    : [];
            }

            $sendEk = $msgKey . '_send_enabled';
            $liveValues[$sendEk] = (bool) ($currentCfg[$sendEk] ?? true);
        }

        foreach ($segments as $segment) {
            $liveValues['booking_status_invoice_customer_' . $segment] = $request->boolean('booking_status_invoice_customer_' . $segment);
            $liveValues['booking_status_invoice_provider_' . $segment] = $request->boolean('booking_status_invoice_provider_' . $segment);
        }

        // If "send invoice" is on but no Meta template is configured for that status (per-tab or fallback), turn invoice off
        // instead of blocking the whole form — validated POST often sends empty status template IDs and would overwrite DB.
        $effectiveCfg = array_merge($currentCfg, $liveValues);
        $invoiceCoercedOff = false;
        foreach ($segments as $segment) {
            $ick = 'booking_status_invoice_customer_' . $segment;
            $ipk = 'booking_status_invoice_provider_' . $segment;
            if (! empty($liveValues[$ick]) && ! BookingWhatsAppNotificationService::hasResolvedStatusWaTemplate($effectiveCfg, 'customer', $segment)) {
                $liveValues[$ick] = false;
                $invoiceCoercedOff = true;
            }
            if (! empty($liveValues[$ipk]) && ! BookingWhatsAppNotificationService::hasResolvedStatusWaTemplate($effectiveCfg, 'provider', $segment)) {
                $liveValues[$ipk] = false;
                $invoiceCoercedOff = true;
            }
        }

        $liveValues = array_replace($currentCfg, $liveValues);

        BusinessSettings::updateOrCreate(
            [
                'key_name' => BookingWhatsAppNotificationService::SETTINGS_KEY,
                'settings_type' => BookingWhatsAppNotificationService::SETTINGS_TYPE,
            ],
            [
                'live_values' => $liveValues,
                'mode' => 'live',
                'is_active' => 1,
            ]
        );

        Toastr::success(translate('successfully_updated'));
        if ($invoiceCoercedOff) {
            Toastr::warning(translate('WhatsApp_booking_invoice_coerced_off_hint'), translate('WhatsApp'));
        }

        $allowedMain = self::bookingTemplateMainTabKeys();
        $mainTab = (string) $request->input('wa_active_main_tab', 'new-booking');
        if (! in_array($mainTab, $allowedMain, true)) {
            $mainTab = 'new-booking';
        }
        $statusSeg = (string) $request->input('wa_active_status_segment', '');
        $segmentKeys = BookingWhatsAppNotificationService::statusTemplateSegmentKeys();
        if ($mainTab !== 'status') {
            $statusSeg = '';
        } elseif ($statusSeg === '' || ! in_array($statusSeg, $segmentKeys, true)) {
            $statusSeg = $segmentKeys[0] ?? '';
        }

        $redirectQuery = ['tab' => $mainTab];
        if ($mainTab === 'status' && $statusSeg !== '') {
            $redirectQuery['status'] = $statusSeg;
        }

        return redirect()->route('admin.whatsapp.booking-templates.edit', $redirectQuery);
    }

    public function toggleEnabled(Request $request): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $service = app(BookingWhatsAppNotificationService::class);
        $liveValues = $service->getConfig();
        $liveValues['enabled'] = $request->boolean('enabled');
        $liveValues['default_phone_prefix'] = '91';
        $liveValues['apply_default_phone_prefix'] = true;

        BusinessSettings::updateOrCreate(
            [
                'key_name' => BookingWhatsAppNotificationService::SETTINGS_KEY,
                'settings_type' => BookingWhatsAppNotificationService::SETTINGS_TYPE,
            ],
            [
                'live_values' => $liveValues,
                'mode' => 'live',
                'is_active' => 1,
            ]
        );

        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.booking-templates.edit');
    }

    public function toggleMessageSendEnabled(Request $request): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        $allowedKeys = BookingWhatsAppNotificationService::configurableMessageKeys();
        $request->validate([
            'message_key' => ['required', 'string', Rule::in($allowedKeys)],
            'enabled' => 'required|boolean',
            'wa_active_main_tab' => 'nullable|string',
            'wa_active_status_segment' => 'nullable|string',
        ]);

        $service = app(BookingWhatsAppNotificationService::class);
        $liveValues = $service->getConfig();
        $mk = (string) $request->input('message_key');
        $liveValues[$mk . '_send_enabled'] = $request->boolean('enabled');
        $liveValues['default_phone_prefix'] = '91';
        $liveValues['apply_default_phone_prefix'] = true;

        BusinessSettings::updateOrCreate(
            [
                'key_name' => BookingWhatsAppNotificationService::SETTINGS_KEY,
                'settings_type' => BookingWhatsAppNotificationService::SETTINGS_TYPE,
            ],
            [
                'live_values' => $liveValues,
                'mode' => 'live',
                'is_active' => 1,
            ]
        );

        Toastr::success(translate('successfully_updated'));

        $allowedMain = self::bookingTemplateMainTabKeys();
        $mainTab = (string) $request->input('wa_active_main_tab', 'new-booking');
        if (! in_array($mainTab, $allowedMain, true)) {
            $mainTab = 'new-booking';
        }
        $statusSeg = (string) $request->input('wa_active_status_segment', '');
        $segmentKeys = BookingWhatsAppNotificationService::statusTemplateSegmentKeys();
        if ($mainTab !== 'status') {
            $statusSeg = '';
        } elseif ($statusSeg === '' || ! in_array($statusSeg, $segmentKeys, true)) {
            $statusSeg = $segmentKeys[0] ?? '';
        }

        $redirectQuery = ['tab' => $mainTab];
        if ($mainTab === 'status' && $statusSeg !== '') {
            $redirectQuery['status'] = $statusSeg;
        }

        return redirect()->route('admin.whatsapp.booking-templates.edit', $redirectQuery);
    }

    /**
     * Templates with image/video/document headers (or no header) expect zero text header variables.
     * Clear any leftover header mappings so save validation and sends stay consistent.
     */
    private function dropStaleWaHeaderMappingsBeforeValidate(Request $request): void
    {
        $post = $request->post();
        if (!is_array($post)) {
            return;
        }
        foreach (BookingWhatsAppNotificationService::configurableMessageKeys() as $msgKey) {
            $wk = $msgKey . '_wa_tpl_id';
            $rawTid = $post[$wk] ?? null;
            if ($rawTid === null || $rawTid === '') {
                continue;
            }
            $tpl = WhatsAppMarketingTemplate::query()->find((int) $rawTid);
            if (!$tpl || strtoupper((string) $tpl->status) !== 'APPROVED') {
                continue;
            }
            $components = is_array($tpl->components) ? $tpl->components : [];
            $headerPlan = WhatsAppCloudService::resolveHeaderTextParameterPlanFromTemplate(['components' => $components]);
            $expectedHeader = ($headerPlan['format'] ?? '') === 'named'
                ? count($headerPlan['named_param_names'] ?? [])
                : (int) ($headerPlan['positional_count'] ?? 0);
            if ($expectedHeader === 0) {
                $request->merge([$msgKey . '_wa_header_params' => []]);
            }
        }
    }
}
