<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Log;
use Modules\WhatsAppModule\Entities\WhatsAppAiExecution;
use Modules\WhatsAppModule\Entities\WhatsAppConversation;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

class WhatsAppAiSupportOrchestrator
{
    /** Tools that mean the assistant understood / acted — reset unclear-streak counter for this chat. */
    private const PRODUCTIVE_TOOL_NAMES = [
        'get_public_business_info',
        'match_zone_from_address',
        'search_support_knowledge',
        'list_my_booking_summaries',
        'list_my_system_bookings',
        'get_my_booking_details',
        'get_booking_status_by_reference',
        'upsert_my_draft_booking',
        'submit_my_booking_for_human_confirmation',
        'upsert_my_draft_provider_lead',
        'submit_my_provider_lead_for_human_confirmation',
        'request_human_support_handoff',
        'get_booking_issue_escalation_reply',
    ];

    public function __construct(
        protected WhatsAppGeminiSupportClient $gemini,
        protected WhatsAppAiToolExecutor $toolExecutor,
        protected WhatsAppCloudService $whatsAppCloud,
        protected WhatsAppMessagePersistenceService $messagePersistence,
        protected WhatsAppSupportWorkHours $workHours,
        protected WhatsAppPublicCatalogService $catalog,
        protected WhatsAppAiSettingsService $aiSettings,
        protected WhatsAppAiSessionContextService $sessionContext,
        protected WhatsAppAiRuntimeResolver $aiRuntime,
    ) {}

    public function handleInboundMessageId(int $messageId, WhatsAppAiExecutionRecorder $recorder): void
    {
        if (!$this->aiRuntime->aiSupportEnabled()) {
            $recorder->step('orchestrator.guard', 'AI support disabled in config', 'skip', []);
            $recorder->finish('skipped_ai_disabled', ['status' => WhatsAppAiExecution::STATUS_SKIPPED]);

            return;
        }

        try {
            $trigger = WhatsAppMessage::query()->find($messageId);
            if (!$trigger || $trigger->direction !== 'IN') {
                $recorder->step('orchestrator.trigger', 'Inbound message missing or not IN', 'fail', [
                    'message_id' => $messageId,
                ]);
                $recorder->finish('skipped_trigger_invalid', [
                    'status' => WhatsAppAiExecution::STATUS_SKIPPED,
                    'summary' => 'Invalid or missing trigger message',
                ]);

                return;
            }

            $recorder->step('orchestrator.trigger', 'Loaded inbound message', 'ok', [
                'message_id' => $trigger->id,
                'phone' => $trigger->phone,
                'message_type' => $trigger->message_type,
                'preview' => mb_substr((string) $trigger->message_text, 0, 160),
            ]);

            $this->runLocked($trigger, $trigger->phone, $messageId, $recorder);
        } catch (\Throwable $e) {
            Log::error('WhatsApp AI orchestrator failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function runLocked(WhatsAppMessage $trigger, string $phone, int $messageId, WhatsAppAiExecutionRecorder $recorder): void
    {
        $latestIn = WhatsAppMessage::query()
            ->where('phone', $phone)
            ->where('direction', 'IN')
            ->orderByDesc('id')
            ->first();

        if (!$latestIn || (int) $latestIn->id !== $messageId) {
            $recorder->step('orchestrator.latest_in', 'Trigger is not the latest inbound message', 'skip', [
                'expected_id' => $messageId,
                'latest_id' => $latestIn?->id,
            ]);
            $recorder->finish('skipped_not_latest', ['status' => WhatsAppAiExecution::STATUS_SKIPPED]);

            return;
        }

        $recorder->step('orchestrator.latest_in', 'Trigger is latest inbound — continuing', 'ok', []);

        $waUser = WhatsAppUser::query()->where('phone', $phone)->first();
        $handledBy = $waUser?->handled_by;
        if ($handledBy !== null && $handledBy !== '' && strtoupper((string) $handledBy) !== 'AI') {
            $recorder->step('orchestrator.handled_by', 'Chat not assigned to AI', 'skip', [
                'handled_by' => $handledBy,
            ]);
            $recorder->finish('skipped_handled_by', ['status' => WhatsAppAiExecution::STATUS_SKIPPED]);

            return;
        }

        $recorder->step('orchestrator.handled_by', 'Handled by AI (or unassigned)', 'ok', [
            'handled_by' => $handledBy ?: null,
        ]);

        $text = trim((string) $trigger->message_text);

        if ($this->isHumanHandoffIntent($text)) {
            $recorder->step('branch.intent', 'Detected human handoff intent', 'ok', []);
            $outId = $this->sendHumanHandoffReply($phone, $recorder);
            $recorder->finish('human_handoff', ['outbound_id' => $outId]);

            return;
        }

        if ($this->shouldSendGreetingButtonsOnly($phone, $text)) {
            $recorder->step('branch.intent', 'First-message greeting + buttons', 'ok', []);
            $outId = $this->sendWelcomeWithButtons($phone, $recorder);
            $recorder->finish('greeting_buttons', ['outbound_id' => $outId]);

            return;
        }

        $recorder->step('branch.intent', 'Normal Gemini conversation path', 'ok', []);

        $contents = $this->buildGeminiContents($phone, $messageId);
        if ($contents === []) {
            $recorder->step('gemini.context', 'No usable messages for Gemini context', 'fail', []);
            $recorder->finish('no_context', [
                'status' => WhatsAppAiExecution::STATUS_SKIPPED,
                'summary' => 'No usable context for Gemini',
            ]);

            return;
        }

        $recorder->step('gemini.context', 'Built conversation contents for Gemini', 'ok', [
            'turns' => count($contents),
        ]);

        $system = $this->aiSettings->resolvedSystemPrompt();
        $ctx = $this->sessionContext->runtimeAppendixForPhone($phone);
        if ($ctx !== '') {
            $system .= "\n\n".$ctx;
        }
        $tools = $this->aiSettings->mergedToolDeclarations();
        if ($tools === []) {
            Log::info('WhatsApp AI: no tools exposed to Gemini (admin disabled all, or invalid config)');
        }

        $iter = 0;
        $finalText = '';
        $replyKind = 'gemini';
        $hadProductiveToolThisRun = false;
        $hadReportUnclearThisRun = false;
        while ($iter < 8) {
            $iter++;
            $recorder->step('gemini.loop', 'Model turn ' . $iter, 'info', ['iteration' => $iter]);
            $turn = $this->gemini->generateTurn($system, $contents, $tools, $recorder);

            if ($iter === 1 && $tools !== [] && $turn['type'] !== 'function_calls') {
                $reason = $turn['type'] === 'blocked' ? (string) ($turn['reason'] ?? '') : '';
                $plainEmpty = $turn['type'] === 'text' && trim((string) ($turn['text'] ?? '')) === '';
                $retryPlain = ($turn['type'] === 'blocked' && $reason !== 'missing_api_key') || $plainEmpty;
                if ($retryPlain) {
                    Log::warning('WhatsApp AI: Gemini unusable with tools; retrying without tools', [
                        'turn_type' => $turn['type'],
                        'reason' => $reason,
                        'plain_empty' => $plainEmpty,
                    ]);
                    $recorder->step('gemini.retry', 'Retrying Gemini without tools', 'info', [
                        'prior_reason' => $reason,
                        'plain_empty' => $plainEmpty,
                    ]);
                    $turn = $this->gemini->generateTurn($system, $contents, [], $recorder);
                }
            }

            if ($turn['type'] === 'blocked') {
                Log::warning('WhatsApp AI blocked turn', ['reason' => $turn['reason'] ?? '']);
                $finalText = $this->fallbackCustomerMessage();
                $replyKind = 'fallback';

                break;
            }

            if ($turn['type'] === 'text') {
                $finalText = $this->sanitizeCustomerReply($turn['text']);
                if ($finalText === '') {
                    Log::warning('WhatsApp AI: Gemini returned empty text after sanitize');
                    $finalText = $this->fallbackCustomerMessage();
                    $replyKind = 'fallback';
                }

                break;
            }

            if ($turn['type'] !== 'function_calls') {
                $finalText = $this->fallbackCustomerMessage();
                $replyKind = 'fallback';

                break;
            }

            $calls = $turn['calls'];
            $modelParts = [];
            foreach ($calls as $c) {
                $modelParts[] = [
                    'functionCall' => [
                        'name' => $c['name'],
                        'args' => (object) $c['args'],
                    ],
                ];
            }
            $contents[] = ['role' => 'model', 'parts' => $modelParts];

            $userParts = [];
            $toolFinalizeUnclear = false;
            $toolFinalizeExact = null;
            foreach ($calls as $c) {
                $toolName = (string) ($c['name'] ?? '');
                if ($toolName === 'report_unclear_user_intent') {
                    $hadReportUnclearThisRun = true;
                }
                if (in_array($toolName, self::PRODUCTIVE_TOOL_NAMES, true)) {
                    $hadProductiveToolThisRun = true;
                }
                $recorder->step('tool.call', 'Tool: ' . $c['name'], 'info', [
                    'args' => $this->truncateForLog($c['args'] ?? []),
                ]);
                $result = $this->toolExecutor->execute($c['name'], $c['args'], $phone);
                $recorder->step('tool.result', 'Tool result: ' . $c['name'], 'ok', [
                    'result' => $this->truncateForLog($result),
                ]);
                if (is_array($result)) {
                    if (!empty($result['orchestrator_finalize']['send_unclear_handoff_message'])) {
                        $toolFinalizeUnclear = true;
                    }
                    if (!empty($result['orchestrator_finalize']['send_exact_customer_text'])) {
                        $t = trim((string) $result['orchestrator_finalize']['send_exact_customer_text']);
                        if ($t !== '') {
                            $toolFinalizeExact = $t;
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
            $contents[] = ['role' => 'user', 'parts' => $userParts];

            $exitLoopAfterTools = false;
            if ($toolFinalizeUnclear) {
                $finalText = $this->buildUnclearLimitHandoffCustomerMessage($text);
                $replyKind = 'unclear_handoff';
                $exitLoopAfterTools = true;
                $recorder->step('orchestrator.unclear', 'Unclear limit — handoff message prepared', 'ok', []);
            } elseif ($toolFinalizeExact !== null) {
                $finalText = $this->sanitizeCustomerReply($toolFinalizeExact);
                if ($finalText === '') {
                    $finalText = $this->fallbackCustomerMessage();
                    $replyKind = 'fallback';
                } else {
                    $replyKind = 'tool_canned';
                }
                $exitLoopAfterTools = true;
                $recorder->step('orchestrator.tool_finalize', 'Canned customer message from tool', 'ok', []);
            }

            if ($exitLoopAfterTools) {
                break;
            }
        }

        if ($hadProductiveToolThisRun && !$hadReportUnclearThisRun) {
            $this->resetConversationUnclearAttempts($phone);
        }

        if ($finalText === '') {
            $finalText = $this->fallbackCustomerMessage();
            $replyKind = 'fallback';
        }

        if ($replyKind === 'unclear_handoff') {
            $this->resetConversationUnclearAttempts($phone);
        }

        $out = $this->messagePersistence->persistOutboundPlaceholder($phone, $finalText, 'AI');
        $err = null;
        $graph = null;
        $waId = $this->whatsAppCloud->sendText($phone, $finalText, $err, $graph);
        $recorder->step('whatsapp.send', 'Send text via WhatsApp Cloud API', $err ? 'fail' : 'ok', [
            'graph_message_id' => $waId,
            'error' => $err ? mb_substr($err, 0, 500) : null,
            'reply_kind' => $replyKind,
        ]);
        if ($waId) {
            $this->messagePersistence->attachWaMessageId($out, $waId);
        }

        $outcome = match ($replyKind) {
            'fallback' => 'gemini_fallback',
            'unclear_handoff' => 'unclear_handoff',
            'tool_canned' => 'tool_canned_reply',
            default => 'gemini_reply',
        };
        $recorder->finish($outcome, ['outbound_id' => $out->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function truncateForLog(mixed $data, int $max = 1200): mixed
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json !== false && strlen($json) <= $max) {
            return $data;
        }

        return ['_truncated' => true, 'preview' => mb_substr($json !== false ? $json : '', 0, $max)];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildGeminiContents(string $phone, int $triggerMessageId): array
    {
        $rows = WhatsAppMessage::query()
            ->where('phone', $phone)
            ->orderByDesc('id')
            ->limit(28)
            ->get(['id', 'message_text', 'direction']);

        $ordered = $rows->reverse()->values();
        $contents = [];

        foreach ($ordered as $row) {
            $t = trim((string) $row->message_text);
            if ($t === '' || (str_starts_with($t, '[') && str_ends_with($t, ']'))) {
                continue;
            }
            $role = $row->direction === 'IN' ? 'user' : 'model';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => mb_substr($t, 0, 3500)]],
            ];
        }

        if ($contents === []) {
            $trigger = $ordered->firstWhere('id', $triggerMessageId);
            if (!$trigger) {
                $trigger = $ordered->reverse()->firstWhere('direction', 'IN');
            }
            if ($trigger) {
                $raw = trim((string) $trigger->message_text);
                if ($raw === '') {
                    $raw = '(Customer sent a non-text or empty message; reply helpfully.)';
                }
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => mb_substr($raw, 0, 3500)]],
                ];
            }
        }

        if ($contents === []) {
            return [];
        }

        if (($contents[0]['role'] ?? '') !== 'user') {
            array_unshift($contents, [
                'role' => 'user',
                'parts' => [['text' => '(Thread continues — respond to the latest customer messages.)']],
            ]);
        }

        return $contents;
    }

    private function sanitizeCustomerReply(string $text): string
    {
        $t = trim($text);
        $t = preg_replace('/^```[a-z]*\s*/i', '', $t) ?? $t;
        $t = preg_replace('/\s*```$/', '', $t) ?? $t;
        $t = $this->stripCustomerUnsafePlaceholders($t);
        $t = $this->stripLeadingModelReasoning($t);

        return mb_substr(trim($t), 0, 3800);
    }

    /**
     * Remove template leaks and meta (e.g. "[insert ...]", tool names) from customer-visible text.
     */
    private function stripCustomerUnsafePlaceholders(string $text): string
    {
        $t = $text;
        $t = preg_replace('/\[[^\]\n]{0,400}?\binsert\b[^\]\n]{0,400}?\]/iu', '', $t) ?? $t;
        $t = preg_replace('/\[[^\]\n]{0,120}?(?:otherwise skip|if available)[^\]\n]{0,320}?\]/iu', '', $t) ?? $t;
        $t = preg_replace('/\bget_public_business_info\b/iu', '', $t) ?? $t;
        $t = preg_replace('/<thinking>[\s\S]*?<\/thinking>/iu', '', $t) ?? $t;
        $t = preg_replace("/\n{3,}/", "\n\n", $t) ?? $t;

        return trim($t);
    }

    /**
     * Gemini sometimes prepends English "thinking" before the real reply; customers must not see it.
     */
    private function stripLeadingModelReasoning(string $text): string
    {
        $t = trim($text);
        if ($t === '') {
            return $t;
        }

        $lines = preg_split('/\r\n|\r|\n/', $t) ?: [];
        while ($lines !== []) {
            $raw = (string) $lines[0];
            if (trim($raw) === '') {
                array_shift($lines);

                continue;
            }
            if ($this->lineLooksLikeModelReasoning($raw)) {
                array_shift($lines);

                continue;
            }

            break;
        }
        $t = trim(implode("\n", $lines));

        if (preg_match('/^The user\b/is', $t)) {
            $t = $this->stripLeadingReasoningSentences($t);
        }

        return trim($t);
    }

    private function lineLooksLikeModelReasoning(string $line): bool
    {
        $s = trim($line);
        if ($s === '') {
            return false;
        }
        if (preg_match('/^The user\b/i', $s)) {
            return true;
        }
        if (preg_match('/^Okay,\s*the user\b/i', $s)) {
            return true;
        }
        if (preg_match('/^I need to (gather|confirm|create|complete|submit|match|find|ask|check|verify|ensure|validate|clarify|get more)\b/i', $s)) {
            return true;
        }
        if (preg_match("/^I'll start by\b/i", $s)) {
            return true;
        }

        return (bool) preg_match('/^I will (gather|confirm|ask|check|verify|need to)\b/i', $s);
    }

    private function stripLeadingReasoningSentences(string $text): string
    {
        $parts = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            return $text;
        }
        $kept = [];
        foreach ($parts as $p) {
            if ($kept === [] && $this->sentenceLooksLikeModelReasoning($p)) {
                continue;
            }
            $kept[] = $p;
        }
        $out = trim(implode(' ', $kept));

        return $out !== '' ? $out : $text;
    }

    private function sentenceLooksLikeModelReasoning(string $sentence): bool
    {
        $s = trim($sentence);
        if (preg_match('/^The user\b/i', $s)) {
            return true;
        }
        if (preg_match('/^Okay,\s*the user\b/i', $s)) {
            return true;
        }
        if (preg_match('/^I need to (gather|confirm|create|complete|submit|match|find|ask|check|verify|ensure|validate|clarify|get more)\b/i', $s)) {
            return true;
        }
        if (preg_match("/^I'll start by\b/i", $s)) {
            return true;
        }

        return (bool) preg_match('/^I will (gather|confirm|ask|check|verify)\b/i', $s);
    }

    private function fallbackCustomerMessage(): string
    {
        $phone = $this->aiSettings->resolvedMessagePlaceholders()['phone'];

        return $phone !== ''
            ? "Thanks for reaching out. We're having a brief technical issue. Please try again in a moment, or call us at {$phone}."
            : 'Thanks for reaching out. We are having a brief technical issue—please try again shortly.';
    }

    private function isHumanHandoffIntent(string $text): bool
    {
        $t = mb_strtolower($text);
        $needles = [
            'human', 'agent', 'person', 'representative', 'operator', 'manager',
            'call me', 'phone call', 'speak to someone', 'real person',
            'insan', 'aadmi', 'bande se baat', 'bande se', 'call karo',
        ];
        $extra = config('whatsapp_ai_support.human_handoff_extra_phrases', []);
        if (is_array($extra)) {
            foreach ($extra as $phrase) {
                if (is_string($phrase) && trim($phrase) !== '') {
                    $needles[] = mb_strtolower(trim($phrase));
                }
            }
        }

        foreach ($needles as $n) {
            if ($n !== '' && str_contains($t, $n)) {
                return true;
            }
        }

        return (bool) preg_match('/\b(talk|speak|connect)\s+(to|with)\s+(a\s+)?(human|person|agent)\b/i', $text);
    }

    private function shouldSendGreetingButtonsOnly(string $phone, string $text): bool
    {
        if (!$this->aiRuntime->greetingButtons()) {
            return false;
        }

        if ($text === '') {
            return false;
        }

        $priorOut = WhatsAppMessage::query()
            ->where('phone', $phone)
            ->where('direction', 'OUT')
            ->count();

        if ($priorOut > 0) {
            return false;
        }

        $t = mb_strtolower(trim($text));

        return (bool) preg_match('/^(hi|hello|hey|hii|hlo|salam|assalam|good\s+(morning|afternoon|evening)|namaste)\b/i', $t);
    }

    private function sendWelcomeWithButtons(string $phone, WhatsAppAiExecutionRecorder $recorder): int
    {
        $brand = $this->aiSettings->resolvedMessagePlaceholders()['brand'];
        $body = "Hello! Welcome to {$brand} 👋\n\nHow can we help you today?";

        $out = $this->messagePersistence->persistOutboundPlaceholder($phone, $body . "\n[Quick actions]", 'AI');
        $err = null;
        $waId = $this->whatsAppCloud->sendInteractiveButtons($phone, $body, [
            ['id' => 'act_book', 'title' => 'Book a service'],
            ['id' => 'act_provider', 'title' => 'Join as provider'],
            ['id' => 'act_human', 'title' => 'Talk to a person'],
        ], $err);

        $recorder->step('whatsapp.send', 'Send interactive greeting buttons', $err ? 'fail' : 'ok', [
            'graph_message_id' => $waId,
            'error' => $err ? mb_substr($err, 0, 500) : null,
        ]);
        if ($waId) {
            $this->messagePersistence->attachWaMessageId($out, $waId);
        }

        return (int) $out->id;
    }

    private function sendHumanHandoffReply(string $phone, WhatsAppAiExecutionRecorder $recorder): int
    {
        WhatsAppUser::markHumanSupportRequested($phone);

        $inHours = $this->workHours->isWithinSupportHours();
        $schedule = $this->workHours->humanReadableSchedule();
        $msg = $this->aiSettings->handoffMessageForCustomer($inHours);

        $recorder->step('handoff.context', 'Support hours check', 'ok', [
            'in_hours' => $inHours,
            'schedule' => $schedule,
        ]);

        $out = $this->messagePersistence->persistOutboundPlaceholder($phone, $msg, 'AI');
        $err = null;
        $waId = $this->whatsAppCloud->sendText($phone, $msg, $err);
        $recorder->step('whatsapp.send', 'Send human handoff text', $err ? 'fail' : 'ok', [
            'graph_message_id' => $waId,
            'error' => $err ? mb_substr($err, 0, 500) : null,
        ]);
        if ($waId) {
            $this->messagePersistence->attachWaMessageId($out, $waId);
        }

        return (int) $out->id;
    }

    private function resetConversationUnclearAttempts(string $phone): void
    {
        if ($phone === '') {
            return;
        }
        try {
            $conv = WhatsAppConversation::query()->where('phone', $phone)->first();
            if ($conv && (int) ($conv->ai_unclear_attempts ?? 0) !== 0) {
                $conv->ai_unclear_attempts = 0;
                $conv->save();
            }
        } catch (\Throwable $e) {
            Log::debug('WhatsApp AI: reset ai_unclear_attempts skipped', ['message' => $e->getMessage()]);
        }
    }

    private function buildUnclearLimitHandoffCustomerMessage(string $lastCustomerText): string
    {
        $lastCustomerText = trim($lastCustomerText);
        $inHours = $this->workHours->isWithinSupportHours();
        $p = $this->aiSettings->resolvedMessagePlaceholders();
        $schedule = $p['schedule'];
        $displayPhone = $p['phone'];
        $callBit = $displayPhone !== '' ? " Please call our support team at {$displayPhone}." : ' Please call our support team.';

        $style = $this->inferCustomerLanguageStyle($lastCustomerText);

        if ($style === 'hinglish') {
            if ($inHours) {
                $msg = "Sorry, main ab bhi properly samajh nahi pa raha what you need from this message.{$callBit} We're in working hours right now ({$schedule}) — they'll help you quickly. Thanks!";
            } else {
                $msg = "Sorry, main ab bhi properly samajh nahi pa raha what you need.{$callBit} Team abhi working hours ke bahar hai — we're available {$schedule}. You can still message here or try calling. Thanks!";
            }

            return $this->sanitizeCustomerReply($msg);
        }

        if ($inHours) {
            $msg = "I'm sorry — I'm not able to understand what you need from this message.{$callBit} We're in our support hours now ({$schedule}), so someone can take it from here. Thanks!";
        } else {
            $msg = "I'm sorry — I'm not able to understand what you need from this message.{$callBit} We're currently outside support hours; we're available {$schedule}. Please leave your message here or try calling — we'll follow up. Thanks!";
        }

        return $this->sanitizeCustomerReply($msg);
    }

    /**
     * Rough style for closing copy only (English vs Hinglish Roman).
     */
    private function inferCustomerLanguageStyle(string $text): string
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return 'english';
        }
        if (preg_match('/\p{Devanagari}/u', $text)) {
            return 'hinglish';
        }
        if (preg_match('/\b(kya|kyun|kyu|nahi|nahin|haan|hai|ho|hoon|aap|mujhe|mera|meri|hum|ko|se|par|bas|thik|theek|chahiye|chahie|batao|bata|samajh|matlab|kuch|yeh|ye|wala|wali|kar|karo|karna|abhi|phir|toh|mat|please|thanks)\b/u', $t)) {
            return 'hinglish';
        }

        return 'english';
    }
}
