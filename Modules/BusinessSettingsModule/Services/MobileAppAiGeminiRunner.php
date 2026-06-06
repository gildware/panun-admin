<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Log;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\BusinessSettingsModule\Entities\MobileAppAiMessage;
use Modules\UserManagement\Entities\User;
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
        protected MobileAppAiGeminiHealthService $geminiHealth,
        protected WhatsAppGeminiSupportClient $gemini,
        protected MobileAppAiToolExecutor $toolExecutor,
        protected MobileAppAiSessionContextService $sessionContext,
        protected MobileAppAiSupportToolPolicy $supportToolPolicy,
        protected WhatsAppLeadLifecycleService $leadLifecycle,
        protected MobileAppAiHandoffService $handoff,
        protected MobileAppAiBookingUiPresenter $bookingUi,
    ) {}

    /**
     * @return array{reply: string, cart_updated: bool, ui?: mixed}
     */
    public function generateReply(User $user, MobileAppAiConversation $conversation, string $domain = ''): array
    {
        $phone = $this->normalizePhone($user->phone);
        $system = $this->settings->resolvedSystemPrompt();
        $system .= "\n\n".$this->sessionContext->runtimeAppendixForUser($user);
        $system .= "\n\n".MobileAppAiDataAuthorityPolicy::promptAppendix();
        if ($domain !== '') {
            $system .= "\n\n## Active domain: **{$domain}** — use only tools relevant to this domain.";
        }
        $lastUserText = $this->lastUserMessageText($conversation);
        $system .= "\n\n## Channel\nYou are **Panun Kaergar's** customer support expert inside the mobile app.\n"
            .MobileAppAiReplyStyle::brevityRules()
            ."\nYou **understand** the customer message, **call tools** to fetch/act on live data, then **write** a natural reply in their language (English or Roman Hinglish)."
            .' When service + time + address are in one message, call **manage_app_booking** once with action=apply.'
            ."\n\n".MobileAppAiReplyStyle::languageAppendixForText($lastUserText);

        $contents = $this->buildGeminiContents($conversation);
        if ($contents === []) {
            return ['reply' => __('mobile_app_ai.empty_context'), 'cart_updated' => false];
        }

        $tools = $this->settings->mergedToolDeclarations();
        if ($domain !== '') {
            $tools = $this->supportToolPolicy->filterDeclarationsForDomain($tools, $domain);
        }
        $tools = $this->supportToolPolicy->filterDeclarations($tools);
        $model = $this->runtime->geminiModel();
        $maxRounds = max(8, (int) config('whatsappmodule.ai_gemini_max_tool_rounds', 6));
        $cartUpdated = false;
        $lastUi = null;
        $unexpectedToolRetry = false;

        $iter = 0;
        while ($iter < $maxRounds) {
            $iter++;
            $turn = $this->gemini->generateTurn($system, $contents, $tools, null, $model);

            if ($iter === 1 && $tools !== [] && $turn['type'] !== 'function_calls') {
                $reason = $turn['type'] === 'blocked' ? (string) ($turn['reason'] ?? '') : '';
                $plainEmpty = $turn['type'] === 'text' && trim((string) ($turn['text'] ?? '')) === '';
                if ($plainEmpty) {
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [[
                            'text' => 'Call the right tool now (manage_customer_cart, manage_app_booking, list_my_system_bookings, or get_customer_cart_summary). Do not reply with text only.',
                        ]],
                    ];

                    continue;
                }
                if ($turn['type'] === 'blocked' && $reason !== 'missing_api_key') {
                    Log::warning('Mobile app AI blocked', ['reason' => $reason]);

                    return $this->fallbackTurn($cartUpdated, $lastUi);
                }
            }

            if ($turn['type'] === 'blocked') {
                $reason = (string) ($turn['reason'] ?? '');
                if (
                    ! $unexpectedToolRetry
                    && $reason === 'finish_UNEXPECTED_TOOL_CALL'
                    && $tools !== []
                ) {
                    $unexpectedToolRetry = true;
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [[
                            'text' => 'Use exactly ONE tool call this turn. If the customer gave service + time + address together, call manage_app_booking once with action=apply and fields service, when, address (and variation/provider if mentioned). Do not chain multiple booking tools in one turn.',
                        ]],
                    ];

                    continue;
                }

                Log::warning('Mobile app AI blocked', ['reason' => $reason]);

                return $this->fallbackTurn($cartUpdated, $lastUi);
            }

            if ($turn['type'] === 'text') {
                $text = trim((string) ($turn['text'] ?? ''));
                if ($text === '') {
                    return $this->fallbackTurn($cartUpdated, $lastUi);
                }

                $this->geminiHealth->markHealthy();

                return [
                    'reply' => MobileAppAiReplyStyle::sanitizeCustomerFacing($text),
                    'cart_updated' => $cartUpdated,
                    'ui' => $lastUi,
                ];
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
            $bookingReply = null;
            $cartReply = null;
            foreach ($turn['calls'] as $c) {
                $toolName = (string) $c['name'];
                $result = $this->toolExecutor->execute(
                    $toolName,
                    is_array($c['args'] ?? null) ? $c['args'] : [],
                    $user,
                    $phone
                );

                $finalize = $this->extractFinalizeReply($result);
                if (isset($result['ui']) && is_array($result['ui'])) {
                    $lastUi = $result['ui'];
                }

                if ($finalize !== null) {
                    return ['reply' => $finalize, 'cart_updated' => $cartUpdated, 'ui' => $lastUi];
                }

                if (
                    ($toolName === 'manage_app_booking' || $toolName === 'add_service_to_customer_cart')
                    && ($result['ok'] ?? false)
                    && ($result['cart_updated'] ?? false)
                ) {
                    $cartUpdated = true;
                }

                if ($toolName === 'manage_app_booking') {
                    $msg = trim((string) ($result['customer_message'] ?? ''));
                    if ($msg !== '') {
                        $bookingReply = $msg;
                        $fresh = $conversation->fresh() ?? $conversation;
                        $draft = $fresh->booking_draft;
                        if (is_array($draft)) {
                            $ui = $this->bookingUi->buildForDraft(
                                ($result['cart_updated'] ?? false) ? ['step' => 'done'] : $draft
                            );
                            if ($ui !== null) {
                                $lastUi = $ui;
                            }
                        }
                    }
                }

                if ($toolName === 'manage_customer_cart' || $toolName === 'get_customer_cart_summary') {
                    if ($toolName === 'manage_customer_cart' && ($result['cart_updated'] ?? false) === true) {
                        $cartUpdated = true;
                    }
                    if (isset($result['ui']) && is_array($result['ui'])) {
                        $lastUi = $result['ui'];
                    }
                    $msg = trim((string) ($result['customer_message'] ?? ''));
                    if ($msg !== '') {
                        $cartReply = $msg;
                        if ($toolName === 'manage_customer_cart') {
                            $fresh = $conversation->fresh() ?? $conversation;
                            $draft = $fresh->booking_draft;
                            if (is_array($draft)) {
                                $ui = $this->bookingUi->buildForDraft(
                                    ($result['cart_updated'] ?? false) ? ['step' => 'done'] : $draft
                                );
                                if ($ui !== null) {
                                    $lastUi = $ui;
                                }
                            }
                        }
                    }
                }

                $userParts[] = [
                    'functionResponse' => [
                        'name' => $c['name'],
                        'response' => $result,
                    ],
                ];
            }

            if ($bookingReply !== null && count($turn['calls']) === 1) {
                $this->geminiHealth->markHealthy();

                return [
                    'reply' => MobileAppAiReplyStyle::sanitizeCustomerFacing($bookingReply),
                    'cart_updated' => $cartUpdated,
                    'ui' => $lastUi,
                ];
            }

            if ($cartReply !== null && count($turn['calls']) === 1) {
                $this->geminiHealth->markHealthy();

                return [
                    'reply' => MobileAppAiReplyStyle::sanitizeCustomerFacing($cartReply),
                    'cart_updated' => $cartUpdated,
                    'ui' => $lastUi,
                ];
            }

            foreach ($turn['calls'] as $c) {
                if ((string) ($c['name'] ?? '') !== 'manage_app_booking') {
                    continue;
                }
                foreach ($userParts as $part) {
                    $response = $part['functionResponse']['response'] ?? null;
                    if (! is_array($response) || ($response['ok'] ?? true)) {
                        continue;
                    }
                    $msg = trim((string) ($response['customer_message'] ?? ''));
                    if ($msg !== '') {
                        return ['reply' => $msg, 'cart_updated' => $cartUpdated, 'ui' => $lastUi];
                    }
                }
            }

            $contents[] = ['role' => 'user', 'parts' => $userParts];
        }

        return $this->fallbackTurn($cartUpdated, $lastUi);
    }

    /**
     * @return array{reply: string, cart_updated: bool, ui?: mixed}
     */
    private function fallbackTurn(bool $cartUpdated, mixed $lastUi): array
    {
        $this->geminiHealth->markUnhealthy();

        return [
            'reply' => __('mobile_app_ai.service_unavailable'),
            'cart_updated' => $cartUpdated,
            'ui' => null,
        ];
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

    private function lastUserMessageText(MobileAppAiConversation $conversation): string
    {
        $row = MobileAppAiMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('source', MobileAppAiMessage::SOURCE_MOBILE_APP)
            ->where('role', 'user')
            ->orderByDesc('id')
            ->first();

        return trim((string) ($row->body ?? ''));
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function extractFinalizeReply(array $result): ?string
    {
        $fin = $result['orchestrator_finalize'] ?? null;
        if (! is_array($fin)) {
            return null;
        }
        if (! empty($fin['send_exact_customer_text'])) {
            return trim((string) $fin['send_exact_customer_text']);
        }
        if (! empty($fin['send_unclear_handoff_message'])) {
            return $this->handoff->unclearFallbackMessage();
        }

        return null;
    }
}
