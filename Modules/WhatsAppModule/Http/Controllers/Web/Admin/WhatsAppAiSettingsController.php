<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\WhatsAppModule\Entities\WhatsAppAiExecution;
use Modules\WhatsAppModule\Services\WhatsAppAiPromptBuilder;
use Modules\WhatsAppModule\Services\WhatsAppAiSettingsService;
use Modules\WhatsAppModule\Services\WhatsAppAiToolExecutor;

class WhatsAppAiSettingsController extends Controller
{
    use AuthorizesRequests;

    /** @var list<string> */
    public const TABS = ['status', 'flow', 'access', 'tools', 'prompt', 'executions'];

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
            'executions',
            'executionDetail'
        ));
    }

    private function normalizeTab(?string $tab): string
    {
        $t = strtolower(trim((string) $tab));

        return in_array($t, self::TABS, true) ? $t : 'status';
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

        abort(400, 'Unknown action');
    }
}
