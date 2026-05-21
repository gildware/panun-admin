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
use Modules\BusinessSettingsModule\Services\MobileAppAiSettingsService;
use Modules\WhatsAppModule\Services\WhatsAppAiPromptBuilder;

class MobileAppConfigurationController extends Controller
{
    use AuthorizesRequests;

    public const TABS = ['ai_config', 'ai_chat'];

    public function __construct(
        protected MobileAppAiSettingsService $settingsService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('ai_configuration_view');

        $tab = $this->normalizeTab($request->query('tab'));
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

        return view('businesssettingsmodule::admin.mobile-app-configuration.index', [
            'tab' => $tab,
            'tabs' => [
                ['id' => 'ai_config', 'label' => translate('AI_Config')],
                ['id' => 'ai_chat', 'label' => translate('AI_Chat')],
            ],
            'settings' => $settings,
            'whatsappBasePromptPreview' => mb_substr(WhatsAppAiPromptBuilder::baseSystemPrompt(), 0, 1200).'…',
            'resolvedPromptPreview' => mb_substr($this->settingsService->resolvedSystemPrompt(), 0, 2000),
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'messages' => $messages,
        ]);
    }

    public function updateAiConfig(Request $request): RedirectResponse
    {
        $this->authorize('ai_configuration_view');

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

        return redirect()->route('admin.mobile-app-configuration.index', ['tab' => 'ai_config']);
    }

    private function normalizeTab(?string $tab): string
    {
        $tab = (string) $tab;
        if (in_array($tab, self::TABS, true)) {
            return $tab;
        }

        return 'ai_config';
    }
}
