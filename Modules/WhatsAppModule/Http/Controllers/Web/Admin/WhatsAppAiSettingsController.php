<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\WhatsAppModule\Entities\WhatsAppAiExecution;
use Modules\WhatsAppModule\Services\WhatsAppAiPlayground;
use Modules\WhatsAppModule\Services\WhatsAppAiPromptBuilder;
use Modules\WhatsAppModule\Services\WhatsAppAiSettingsService;
use Modules\WhatsAppModule\Services\WhatsAppAiToolExecutor;
use Modules\WhatsAppModule\Services\WhatsAppTemplateButtonValidator;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

class WhatsAppAiSettingsController extends Controller
{
    use AuthorizesRequests;

    /** @var list<string> */
    public const TABS = ['summary', 'playground', 'prompt', 'executions', 'tools', 'ai_config', 'business_config', 'message_config', 'access', 'flow'];

    public function __construct(
        protected WhatsAppAiSettingsService $aiSettingsService
    ) {}

    public function edit(Request $request): View
    {
        $this->authorize('whatsapp_chat_view');

        $tab = $this->normalizeTab($request->query('tab'));

        if ($tab === 'business_config' && $request->boolean('edit')) {
            $this->authorize('whatsapp_chat_assign');
        }

        $businessConfigEditMode = $tab === 'business_config' && $request->boolean('edit');

        $businessConfigRows = [];
        if ($tab === 'business_config') {
            $businessConfigRows = $this->aiSettingsService->businessConfigurationViewRows();
        }

        $settings = $this->aiSettingsService->settings();
        $runtime = $this->aiSettingsService->adminRuntimeStatus();
        $toolsForAdmin = $this->aiSettingsService->toolReferenceForAdmin();
        $basePrompt = WhatsAppAiPromptBuilder::baseSystemPrompt();
        $resolvedPrompt = $this->aiSettingsService->resolvedSystemPrompt();
        $liveFlowMermaid = $this->aiSettingsService->liveFlowMermaidFromSettings();
        $allowedLines = WhatsAppAiPromptBuilder::defaultAllowedAccessLines();
        $forbiddenLines = WhatsAppAiPromptBuilder::defaultForbiddenAccessLines();

        $customerMessageDefaults = [
            'handoff_in' => $this->aiSettingsService->defaultHandoffMessageForCustomer(true),
            'handoff_out' => $this->aiSettingsService->defaultHandoffMessageForCustomer(false),
            'greeting_body' => $this->aiSettingsService->mergeCustomerMessagePlaceholders(
                $this->aiSettingsService->defaultGreetingMessageTemplate(),
                null
            ),
            'non_text' => $this->aiSettingsService->mergeCustomerMessagePlaceholders(
                $this->aiSettingsService->defaultNonTextInboundMessageTemplate(),
                null
            ),
        ];
        /** Pre-fill message_config textareas when DB is empty so admins edit the effective default directly. */
        $messageConfigEditorDefaults = [
            'db_greeting_message' => $this->aiSettingsService->defaultGreetingMessageTemplate(),
            'handoff_message_in_hours' => $customerMessageDefaults['handoff_in'],
            'handoff_message_out_hours' => $customerMessageDefaults['handoff_out'],
            'db_non_text_inbound_message' => $this->aiSettingsService->defaultNonTextInboundMessageTemplate(),
        ];
        $greetingButtonRows = $this->aiSettingsService->templateStyleButtonRowsForForm('greeting');
        $handoffInButtonRows = $this->aiSettingsService->templateStyleButtonRowsForForm('handoff_in');
        $handoffOutButtonRows = $this->aiSettingsService->templateStyleButtonRowsForForm('handoff_out');
        $nonTextButtonRows = $this->aiSettingsService->templateStyleButtonRowsForForm('non_text');
        $placeholderResolved = $this->aiSettingsService->resolvedMessagePlaceholders();
        $aiBehaviorSummary = $this->aiSettingsService->aiBehaviorSummary();
        $supportWorkDaysEffective = $this->aiSettingsService->effectiveSupportWorkDays();

        $executions = null;
        $executionDetail = null;
        if ($tab === 'executions') {
            $executions = WhatsAppAiExecution::query()
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString();
            $detailId = (int) $request->query('id', 0);
            if ($detailId > 0) {
                $executionDetail = WhatsAppAiExecution::query()->find($detailId);
            }
        }

        $playgroundDefaultPhone = WhatsAppAiPlayground::defaultSandboxPhone();
        $playgroundScenarios = self::playgroundScenarioPresets();

        return view('whatsappmodule::admin.ai-settings', compact(
            'tab',
            'settings',
            'runtime',
            'toolsForAdmin',
            'basePrompt',
            'resolvedPrompt',
            'liveFlowMermaid',
            'allowedLines',
            'forbiddenLines',
            'customerMessageDefaults',
            'messageConfigEditorDefaults',
            'greetingButtonRows',
            'handoffInButtonRows',
            'handoffOutButtonRows',
            'nonTextButtonRows',
            'placeholderResolved',
            'aiBehaviorSummary',
            'supportWorkDaysEffective',
            'businessConfigEditMode',
            'executions',
            'executionDetail',
            'businessConfigRows',
            'playgroundDefaultPhone',
            'playgroundScenarios'
        ));
    }

    /**
     * Preset customer lines for the AI playground (admin testing only).
     *
     * @return list<array{id: string, label: string, text: string}>
     */
    private static function playgroundScenarioPresets(): array
    {
        return [
            ['id' => 'greeting', 'label' => 'Greeting', 'text' => 'Hi'],
            ['id' => 'hinglish_help', 'label' => 'Service help (Hinglish)', 'text' => 'Mujhe electrician chahiye kal shaam ko'],
            ['id' => 'date', 'label' => 'Today’s date', 'text' => 'What date is today?'],
            ['id' => 'booking_ref', 'label' => 'Booking reference', 'text' => 'I need status for booking PK07APR26001'],
            ['id' => 'reschedule', 'label' => 'Reschedule ask', 'text' => 'I need to reschedule my submitted booking'],
            ['id' => 'human', 'label' => 'Human handoff', 'text' => 'I want to talk to a human agent'],
        ];
    }

    private function normalizeTab(?string $tab): string
    {
        $t = strtolower(trim((string) $tab));
        $legacy = [
            'status' => 'ai_config',
            'customer_messages' => 'message_config',
        ];
        if (isset($legacy[$t])) {
            $t = $legacy[$t];
        }

        return in_array($t, self::TABS, true) ? $t : 'summary';
    }

    private function redirectAfterSave(Request $request, string $defaultTab): RedirectResponse
    {
        $tab = $this->normalizeTab($request->input('return_tab', $defaultTab));
        $params = ['tab' => $tab];
        if ($tab === 'message_config') {
            $st = $this->normalizeMessageConfigSubtab($request->input('msg_subtab'));
            if ($st !== null) {
                $params['msg_subtab'] = $st;
            }
        }

        return redirect()->route('admin.whatsapp.ai-settings.edit', $this->withSocialInboxChannel($params));
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function withSocialInboxChannel(array $query): array
    {
        return array_merge(['channel' => SocialInboxChannel::current()], $query);
    }

    private function normalizeMessageConfigSubtab(mixed $value): ?string
    {
        $allowed = ['greeting', 'handoff_in', 'handoff_out', 'non_text'];
        $s = is_string($value) ? trim($value) : '';

        return in_array($s, $allowed, true) ? $s : null;
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('whatsapp_chat_assign');

        $row = $this->aiSettingsService->settings();

        if ($request->has('save_access')) {
            $validator = Validator::make($request->all(), [
                'allowed_policy' => 'nullable|string|max:65000',
                'forbidden_policy' => 'nullable|string|max:65000',
            ]);
            if ($validator->fails()) {
                return redirect()->route('admin.whatsapp.ai-settings.edit', $this->withSocialInboxChannel(['tab' => 'access']))
                    ->withErrors($validator)
                    ->withInput();
            }
            $data = $validator->validated();
            $row->allowed_policy = $data['allowed_policy'] ?? null;
            $row->forbidden_policy = $data['forbidden_policy'] ?? null;
            $row->save();
            Toastr::success(__('whatsapp_ai.settings_updated'));

            return $this->redirectAfterSave($request, 'access');
        }

        if ($request->has('save_tools')) {
            $config = [];
            foreach (WhatsAppAiToolExecutor::functionDeclarations() as $decl) {
                $name = (string) ($decl['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $enabled = $request->has('tools_enabled.' . $name)
                    ? $request->boolean('tools_enabled.' . $name)
                    : true;
                $desc = $request->input('tools_description.' . $name);
                $descTrim = is_string($desc) ? trim($desc) : '';
                $config[$name] = [
                    'enabled' => $enabled,
                    'description' => $descTrim !== '' ? mb_substr($descTrim, 0, 4000) : null,
                ];
            }
            $row->tools_config = $config;
            $row->save();
            Toastr::success(__('whatsapp_ai.settings_updated'));

            return $this->redirectAfterSave($request, 'tools');
        }

        if ($request->has('save_prompt')) {
            $validator = Validator::make($request->all(), [
                'use_full_custom_prompt' => 'nullable|boolean',
                'custom_system_prompt' => 'nullable|string|max:100000',
                'assistant_persona' => 'nullable|string|max:65000',
                'prompt_addendum' => 'nullable|string|max:65000',
            ]);
            if ($validator->fails()) {
                return redirect()->route('admin.whatsapp.ai-settings.edit', $this->withSocialInboxChannel(['tab' => 'prompt']))
                    ->withErrors($validator)
                    ->withInput();
            }
            $data = $validator->validated();

            $row->use_full_custom_prompt = $request->boolean('use_full_custom_prompt');
            $row->custom_system_prompt = $data['custom_system_prompt'] ?? null;
            $row->assistant_persona = $data['assistant_persona'] ?? null;
            $row->prompt_addendum = $data['prompt_addendum'] ?? null;
            $row->save();
            Toastr::success(__('whatsapp_ai.settings_updated'));

            return $this->redirectAfterSave($request, 'prompt');
        }

        if ($request->has('save_ai_config')) {
            $validator = Validator::make($request->all(), [
                'db_ai_support_enabled' => ['nullable', 'string', Rule::in(['', '0', '1'])],
                'db_ai_dispatch_sync' => ['nullable', 'string', Rule::in(['', '0', '1'])],
                'db_gemini_model' => 'nullable|string|max:255',
                'db_queue_connection' => 'nullable|string|max:64',
            ]);
            if ($validator->fails()) {
                return redirect()->route('admin.whatsapp.ai-settings.edit', $this->withSocialInboxChannel(['tab' => 'ai_config']))
                    ->withErrors($validator)
                    ->withInput();
            }

            $tri = static function (?string $v): ?bool {
                if ($v === null || $v === '') {
                    return null;
                }

                return (bool) (int) $v;
            };

            $nullIfEmpty = static fn (?string $s): ?string => ($s !== null && trim($s) !== '') ? trim($s) : null;

            $row->db_ai_support_enabled = $tri($request->input('db_ai_support_enabled'));
            $row->db_ai_dispatch_sync = $tri($request->input('db_ai_dispatch_sync'));
            $row->db_gemini_model = $nullIfEmpty($request->input('db_gemini_model'));
            $row->db_queue_connection = $nullIfEmpty($request->input('db_queue_connection'));
            $row->save();
            Toastr::success(__('whatsapp_ai.settings_updated'));

            return $this->redirectAfterSave($request, 'ai_config');
        }

        if ($request->has('save_business_config')) {
            $opValidator = Validator::make($request->all(), [
                'db_support_hours_start' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
                'db_support_hours_end' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
                'db_support_days' => ['required', 'array', 'min:1'],
                'db_support_days.*' => ['integer', Rule::in([1, 2, 3, 4, 5, 6, 7])],
                'db_support_phone_display' => 'nullable|string|max:512',
            ]);
            if ($opValidator->fails()) {
                return redirect()->route('admin.whatsapp.ai-settings.edit', $this->withSocialInboxChannel(['tab' => 'business_config', 'edit' => 1]))
                    ->withErrors($opValidator)
                    ->withInput();
            }
            $op = $opValidator->validated();
            $days = array_values(array_unique(array_map('intval', $op['db_support_days'] ?? [])));
            sort($days);
            $days = array_values(array_filter($days, static fn (int $d): bool => $d >= 1 && $d <= 7));
            if ($days === []) {
                return redirect()->route('admin.whatsapp.ai-settings.edit', $this->withSocialInboxChannel(['tab' => 'business_config', 'edit' => 1]))
                    ->withErrors(['db_support_days' => __('whatsapp_ai.support_days_required')])
                    ->withInput();
            }
            $start = $op['db_support_hours_start'];
            $end = $op['db_support_hours_end'];
            $toMinutes = static function (string $hm): int {
                [$h, $m] = array_map('intval', explode(':', $hm) + [0, 0]);

                return $h * 60 + $m;
            };
            if ($toMinutes($start) >= $toMinutes($end)) {
                return redirect()->route('admin.whatsapp.ai-settings.edit', $this->withSocialInboxChannel(['tab' => 'business_config', 'edit' => 1]))
                    ->withErrors(['db_support_hours_end' => __('whatsapp_ai.support_hours_end_after_start')])
                    ->withInput();
            }

            $businessFieldRules = [
                'placeholder_brand' => 'nullable|string|max:512',
                'placeholder_email' => 'nullable|string|max:512',
                'placeholder_website' => 'nullable|string|max:512',
                'placeholder_address' => 'nullable|string|max:8000',
                'placeholder_tagline' => 'nullable|string|max:512',
                'placeholder_provider_onboarding' => 'nullable|string|max:8000',
            ];
            $rules = [];
            foreach ($businessFieldRules as $field => $fieldRules) {
                if ($request->has('override_'.$field)) {
                    $rules[$field] = $fieldRules;
                }
            }
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return redirect()->route('admin.whatsapp.ai-settings.edit', $this->withSocialInboxChannel(['tab' => 'business_config', 'edit' => 1]))
                    ->withErrors($validator)
                    ->withInput();
            }
            $validated = $validator->validated();
            $nullIfEmpty = static fn (?string $s): ?string => ($s !== null && trim($s) !== '') ? trim($s) : null;

            $row->db_support_hours_start = $start;
            $row->db_support_hours_end = $end;
            $row->db_support_days = $days;
            $row->db_support_phone_display = $nullIfEmpty($request->input('db_support_phone_display'));
            $row->db_support_timezone = null;
            $row->placeholder_schedule = null;
            $row->placeholder_phone = null;

            foreach (array_keys($businessFieldRules) as $field) {
                if (! $request->has('override_'.$field)) {
                    $row->{$field} = null;

                    continue;
                }
                $row->{$field} = $nullIfEmpty($validated[$field] ?? $request->input($field));
            }
            $row->save();
            Toastr::success(__('whatsapp_ai.settings_updated'));

            return $this->redirectAfterSave($request, 'business_config');
        }

        if ($request->has('save_message_config')) {
            $buttonRowRules = [
                'greeting_button_rows' => 'nullable|array|max:10',
                'greeting_button_rows.*.kind' => 'nullable|string|in:QUICK_REPLY,URL,PHONE_NUMBER',
                'greeting_button_rows.*.text' => 'nullable|string|max:25',
                'greeting_button_rows.*.url' => 'nullable|string|max:2000',
                'greeting_button_rows.*.phone' => 'nullable|string|max:24',
                'handoff_in_button_rows' => 'nullable|array|max:10',
                'handoff_in_button_rows.*.kind' => 'nullable|string|in:QUICK_REPLY,URL,PHONE_NUMBER',
                'handoff_in_button_rows.*.text' => 'nullable|string|max:25',
                'handoff_in_button_rows.*.url' => 'nullable|string|max:2000',
                'handoff_in_button_rows.*.phone' => 'nullable|string|max:24',
                'handoff_out_button_rows' => 'nullable|array|max:10',
                'handoff_out_button_rows.*.kind' => 'nullable|string|in:QUICK_REPLY,URL,PHONE_NUMBER',
                'handoff_out_button_rows.*.text' => 'nullable|string|max:25',
                'handoff_out_button_rows.*.url' => 'nullable|string|max:2000',
                'handoff_out_button_rows.*.phone' => 'nullable|string|max:24',
                'non_text_button_rows' => 'nullable|array|max:10',
                'non_text_button_rows.*.kind' => 'nullable|string|in:QUICK_REPLY,URL,PHONE_NUMBER',
                'non_text_button_rows.*.text' => 'nullable|string|max:25',
                'non_text_button_rows.*.url' => 'nullable|string|max:2000',
                'non_text_button_rows.*.phone' => 'nullable|string|max:24',
            ];
            $validator = Validator::make($request->all(), array_merge([
                'db_greeting_buttons' => ['nullable', 'string', Rule::in(['', '0', '1'])],
                'db_greeting_message' => 'nullable|string|max:1024',
                'handoff_message_in_hours' => 'nullable|string|max:16000',
                'handoff_message_out_hours' => 'nullable|string|max:16000',
                'db_non_text_inbound_message' => 'nullable|string|max:16000',
            ], $buttonRowRules));
            if ($validator->fails()) {
                return redirect()->route('admin.whatsapp.ai-settings.edit', $this->withSocialInboxChannel([
                    'tab' => 'message_config',
                    'msg_subtab' => $this->normalizeMessageConfigSubtab($request->input('msg_subtab')) ?? 'greeting',
                ]))
                    ->withErrors($validator)
                    ->withInput();
            }

            $buttonPrefixes = [
                'greeting_button_rows' => 'db_greeting_buttons_json',
                'handoff_in_button_rows' => 'db_handoff_in_buttons_json',
                'handoff_out_button_rows' => 'db_handoff_out_buttons_json',
                'non_text_button_rows' => 'db_non_text_buttons_json',
            ];
            foreach ($buttonPrefixes as $prefix => $col) {
                $rows = $this->aiSettingsService->templateButtonRowsFromRequest($request->all(), $prefix);
                $vRows = $this->aiSettingsService->filterRowsForTemplateValidator($rows);
                $built = WhatsAppTemplateButtonValidator::metaButtonsFromRows($vRows);
                if ($built['error'] !== null) {
                    Toastr::error(translate($built['error']));

                    return redirect()->route('admin.whatsapp.ai-settings.edit', $this->withSocialInboxChannel([
                        'tab' => 'message_config',
                        'msg_subtab' => $this->normalizeMessageConfigSubtab($request->input('msg_subtab')) ?? 'greeting',
                    ]))
                        ->withInput();
                }
                $row->{$col} = $built['buttons'] === [] ? null : $built['buttons'];
            }

            $data = $validator->validated();
            $nullIfEmpty = static fn (?string $s): ?string => ($s !== null && trim($s) !== '') ? trim($s) : null;

            $tri = static function (?string $v): ?bool {
                if ($v === null || $v === '') {
                    return null;
                }

                return (bool) (int) $v;
            };

            $row->db_greeting_buttons = $tri($request->input('db_greeting_buttons'));
            $row->db_greeting_message = $nullIfEmpty($data['db_greeting_message'] ?? $request->input('db_greeting_message'));
            $row->handoff_message_in_hours = $nullIfEmpty($data['handoff_message_in_hours'] ?? null);
            $row->handoff_message_out_hours = $nullIfEmpty($data['handoff_message_out_hours'] ?? null);
            $row->db_non_text_inbound_message = $nullIfEmpty($data['db_non_text_inbound_message'] ?? $request->input('db_non_text_inbound_message'));
            $row->save();
            Toastr::success(__('whatsapp_ai.settings_updated'));

            return $this->redirectAfterSave($request, 'message_config');
        }

        abort(400, 'Unknown action');
    }
}
