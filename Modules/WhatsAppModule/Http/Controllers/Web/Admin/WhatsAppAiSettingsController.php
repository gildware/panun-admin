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
use Modules\WhatsAppModule\Services\WhatsAppAiPromptBuilder;
use Modules\WhatsAppModule\Services\WhatsAppAiSettingsService;
use Modules\WhatsAppModule\Services\WhatsAppAiToolExecutor;

class WhatsAppAiSettingsController extends Controller
{
    use AuthorizesRequests;

    /** @var list<string> */
    public const TABS = ['prompt', 'executions', 'tools', 'customer_messages', 'access', 'status', 'flow'];

    public function __construct(
        protected WhatsAppAiSettingsService $aiSettingsService
    ) {}

    public function edit(Request $request): View
    {
        $this->authorize('whatsapp_chat_view');

        $tab = $this->normalizeTab($request->query('tab'));

        $settings = $this->aiSettingsService->settings();
        $runtime = $this->aiSettingsService->adminRuntimeStatus();
        $toolsForAdmin = $this->aiSettingsService->toolReferenceForAdmin();
        $basePrompt = WhatsAppAiPromptBuilder::baseSystemPrompt();
        $resolvedPrompt = $this->aiSettingsService->resolvedSystemPrompt();
        $flowMermaid = $this->aiSettingsService->flowMermaidSource();
        $allowedLines = WhatsAppAiPromptBuilder::defaultAllowedAccessLines();
        $forbiddenLines = WhatsAppAiPromptBuilder::defaultForbiddenAccessLines();

        $customerMessageDefaults = [
            'handoff_in' => $this->aiSettingsService->defaultHandoffMessageForCustomer(true),
            'handoff_out' => $this->aiSettingsService->defaultHandoffMessageForCustomer(false),
            'booking_escalation' => $this->aiSettingsService->defaultBookingProviderEscalationMessage(),
        ];
        $placeholderResolved = $this->aiSettingsService->resolvedMessagePlaceholders();

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

        return view('whatsappmodule::admin.ai-settings', compact(
            'tab',
            'settings',
            'runtime',
            'toolsForAdmin',
            'basePrompt',
            'resolvedPrompt',
            'flowMermaid',
            'allowedLines',
            'forbiddenLines',
            'customerMessageDefaults',
            'placeholderResolved',
            'executions',
            'executionDetail'
        ));
    }

    private function normalizeTab(?string $tab): string
    {
        $t = strtolower(trim((string) $tab));

        return in_array($t, self::TABS, true) ? $t : 'prompt';
    }

    private function redirectAfterSave(Request $request, string $defaultTab): RedirectResponse
    {
        $tab = $this->normalizeTab($request->input('return_tab', $defaultTab));

        return redirect()->route('admin.whatsapp.ai-settings.edit', ['tab' => $tab]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('whatsapp_chat_assign');

        $row = $this->aiSettingsService->settings();

        if ($request->has('reset_flow')) {
            $row->flow_mermaid = null;
            $row->save();
            Toastr::success(__('whatsapp_ai.settings_updated'));

            return $this->redirectAfterSave($request, 'flow');
        }

        if ($request->has('save_flow')) {
            $validator = Validator::make($request->all(), [
                'flow_mermaid' => 'nullable|string|max:30000',
            ]);
            if ($validator->fails()) {
                return redirect()->route('admin.whatsapp.ai-settings.edit', ['tab' => 'flow'])
                    ->withErrors($validator)
                    ->withInput();
            }
            $row->flow_mermaid = $request->input('flow_mermaid');
            $row->save();
            Toastr::success(__('whatsapp_ai.settings_updated'));

            return $this->redirectAfterSave($request, 'flow');
        }

        if ($request->has('save_access')) {
            $validator = Validator::make($request->all(), [
                'allowed_policy' => 'nullable|string|max:65000',
                'forbidden_policy' => 'nullable|string|max:65000',
            ]);
            if ($validator->fails()) {
                return redirect()->route('admin.whatsapp.ai-settings.edit', ['tab' => 'access'])
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
                return redirect()->route('admin.whatsapp.ai-settings.edit', ['tab' => 'prompt'])
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

        if ($request->has('save_customer_messages')) {
            $validator = Validator::make($request->all(), [
                'handoff_message_in_hours' => 'nullable|string|max:16000',
                'handoff_message_out_hours' => 'nullable|string|max:16000',
                'booking_provider_escalation_message' => 'nullable|string|max:16000',
                'placeholder_schedule' => 'nullable|string|max:8000',
                'placeholder_phone' => 'nullable|string|max:512',
                'placeholder_brand' => 'nullable|string|max:512',
                'placeholder_email' => 'nullable|string|max:512',
                'placeholder_website' => 'nullable|string|max:512',
                'placeholder_address' => 'nullable|string|max:8000',
                'placeholder_tagline' => 'nullable|string|max:512',
                'placeholder_custom_1' => 'nullable|string|max:8000',
                'placeholder_custom_2' => 'nullable|string|max:8000',
            ]);
            if ($validator->fails()) {
                return redirect()->route('admin.whatsapp.ai-settings.edit', ['tab' => 'customer_messages'])
                    ->withErrors($validator)
                    ->withInput();
            }
            $data = $validator->validated();
            $nullIfEmpty = static fn (?string $s): ?string => ($s !== null && trim($s) !== '') ? trim($s) : null;

            $row->handoff_message_in_hours = $nullIfEmpty($data['handoff_message_in_hours'] ?? null);
            $row->handoff_message_out_hours = $nullIfEmpty($data['handoff_message_out_hours'] ?? null);
            $row->booking_provider_escalation_message = $nullIfEmpty($data['booking_provider_escalation_message'] ?? null);
            $row->placeholder_schedule = $nullIfEmpty($data['placeholder_schedule'] ?? null);
            $row->placeholder_phone = $nullIfEmpty($data['placeholder_phone'] ?? null);
            $row->placeholder_brand = $nullIfEmpty($data['placeholder_brand'] ?? null);
            $row->placeholder_email = $nullIfEmpty($data['placeholder_email'] ?? null);
            $row->placeholder_website = $nullIfEmpty($data['placeholder_website'] ?? null);
            $row->placeholder_address = $nullIfEmpty($data['placeholder_address'] ?? null);
            $row->placeholder_tagline = $nullIfEmpty($data['placeholder_tagline'] ?? null);
            $row->placeholder_custom_1 = $nullIfEmpty($data['placeholder_custom_1'] ?? null);
            $row->placeholder_custom_2 = $nullIfEmpty($data['placeholder_custom_2'] ?? null);
            $row->save();
            Toastr::success(__('whatsapp_ai.settings_updated'));

            return $this->redirectAfterSave($request, 'customer_messages');
        }

        if ($request->has('save_operational')) {
            $validator = Validator::make($request->all(), [
                'db_ai_support_enabled' => ['nullable', 'string', Rule::in(['', '0', '1'])],
                'db_greeting_buttons' => ['nullable', 'string', Rule::in(['', '0', '1'])],
                'db_ai_dispatch_sync' => ['nullable', 'string', Rule::in(['', '0', '1'])],
                'db_gemini_model' => 'nullable|string|max:255',
                'db_support_hours_start' => ['nullable', 'string', 'max:16', 'regex:/^$|^([01]\d|2[0-3]):[0-5]\d$/'],
                'db_support_hours_end' => ['nullable', 'string', 'max:16', 'regex:/^$|^([01]\d|2[0-3]):[0-5]\d$/'],
                'db_support_timezone' => 'nullable|string|max:64',
                'db_support_phone_display' => 'nullable|string|max:512',
                'db_queue_connection' => 'nullable|string|max:64',
            ]);
            if ($validator->fails()) {
                return redirect()->route('admin.whatsapp.ai-settings.edit', ['tab' => 'status'])
                    ->withErrors($validator)
                    ->withInput();
            }

            $tri = static function (?string $v): ?bool {
                if ($v === null || $v === '') {
                    return null;
                }

                return (bool) (int) $v;
            };

            $row->db_ai_support_enabled = $tri($request->input('db_ai_support_enabled'));
            $row->db_greeting_buttons = $tri($request->input('db_greeting_buttons'));
            $row->db_ai_dispatch_sync = $tri($request->input('db_ai_dispatch_sync'));

            $nullIfEmpty = static fn (?string $s): ?string => ($s !== null && trim($s) !== '') ? trim($s) : null;
            $row->db_gemini_model = $nullIfEmpty($request->input('db_gemini_model'));
            $row->db_support_hours_start = $nullIfEmpty($request->input('db_support_hours_start'));
            $row->db_support_hours_end = $nullIfEmpty($request->input('db_support_hours_end'));
            $row->db_support_timezone = $nullIfEmpty($request->input('db_support_timezone'));
            $row->db_support_phone_display = $nullIfEmpty($request->input('db_support_phone_display'));
            $row->db_queue_connection = $nullIfEmpty($request->input('db_queue_connection'));
            $row->save();
            Toastr::success(__('whatsapp_ai.settings_updated'));

            return $this->redirectAfterSave($request, 'status');
        }

        abort(400, 'Unknown action');
    }
}
