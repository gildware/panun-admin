<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Log;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\BusinessSettingsModule\Entities\MobileAppAiMessage;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Services\WhatsAppAiToolExecutor;
use Modules\WhatsAppModule\Services\WhatsAppGeminiSupportClient;
use Modules\WhatsAppModule\Services\WhatsAppLeadLifecycleService;

/**
 * Runs Gemini + optional WhatsApp support tools for mobile in-app AI chat.
 */
class MobileAppAiGeminiRunner
{
    public function __construct(
        protected MobileAppAiSettingsService $settings,
        protected MobileAppAiRuntimeResolver $runtime,
        protected WhatsAppGeminiSupportClient $gemini,
        protected WhatsAppAiToolExecutor $toolExecutor,
        protected MobileAppAiSessionContextService $sessionContext,
        protected MobileAppAiSupportToolPolicy $supportToolPolicy,
        protected WhatsAppLeadLifecycleService $leadLifecycle,
    ) {}

    public function generateReply(User $user, MobileAppAiConversation $conversation): string
    {
        $phone = $this->normalizePhone($user->phone);
        $system = $this->settings->resolvedSystemPrompt();
        $system .= "\n\n".$this->sessionContext->runtimeAppendixForUser($user);
        $system .= "\n\n## Channel\nYou are replying inside the **customer mobile app** AI chat. Keep answers short and actionable.";

        $contents = $this->buildGeminiContents($conversation);
        if ($contents === []) {
            return __('mobile_app_ai.empty_context');
        }

        $tools = $this->settings->mergedToolDeclarations();
        $model = $this->runtime->geminiModel();
        $maxRounds = (int) config('whatsappmodule.ai_gemini_max_tool_rounds', 6);

        $iter = 0;
        while ($iter < $maxRounds) {
            $iter++;
            $turn = $this->gemini->generateTurn($system, $contents, $tools, null, $model);

            if ($iter === 1 && $tools !== [] && $turn['type'] !== 'function_calls') {
                $reason = $turn['type'] === 'blocked' ? (string) ($turn['reason'] ?? '') : '';
                $plainEmpty = $turn['type'] === 'text' && trim((string) ($turn['text'] ?? '')) === '';
                if (($turn['type'] === 'blocked' && $reason !== 'missing_api_key') || $plainEmpty) {
                    $turn = $this->gemini->generateTurn($system, $contents, [], null, $model);
                }
            }

            if ($turn['type'] === 'blocked') {
                Log::warning('Mobile app AI blocked', ['reason' => $turn['reason'] ?? '']);

                return __('mobile_app_ai.fallback_reply');
            }

            if ($turn['type'] === 'text') {
                $text = trim((string) ($turn['text'] ?? ''));

                return $text !== '' ? $text : __('mobile_app_ai.fallback_reply');
            }

            if ($turn['type'] !== 'function_calls') {
                break;
            }

            $modelParts = [];
            foreach ($turn['calls'] as $c) {
                $modelParts[] = [
                    'functionCall' => [
                        'name' => $c['name'],
                        'args' => (object) ($c['args'] ?? []),
                    ],
                ];
            }
            $contents[] = ['role' => 'model', 'parts' => $modelParts];

            $userParts = [];
            foreach ($turn['calls'] as $c) {
                $toolName = (string) $c['name'];
                if (!$this->supportToolPolicy->isAllowed($toolName)) {
                    $result = ['ok' => false, 'error' => 'tool_not_available_on_mobile'];
                } else {
                    $result = $this->toolExecutor->execute(
                        $toolName,
                        is_array($c['args'] ?? null) ? $c['args'] : [],
                        $phone
                    );
                }
                $userParts[] = [
                    'functionResponse' => [
                        'name' => $c['name'],
                        'response' => $result,
                    ],
                ];
            }
            $contents[] = ['role' => 'user', 'parts' => $userParts];
        }

        return __('mobile_app_ai.fallback_reply');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildGeminiContents(MobileAppAiConversation $conversation): array
    {
        $limit = $this->runtime->maxHistoryMessages();
        $rows = MobileAppAiMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('source', MobileAppAiMessage::SOURCE_MOBILE_APP)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $contents = [];
        foreach ($rows as $row) {
            $text = trim((string) $row->body);
            if ($text === '') {
                continue;
            }
            $role = $row->role === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $text]],
            ];
        }

        return $contents;
    }

    private function normalizePhone(?string $phone): string
    {
        $normalized = $this->leadLifecycle->normalizeLeadPhone($phone);

        return $normalized ?? '';
    }
}
