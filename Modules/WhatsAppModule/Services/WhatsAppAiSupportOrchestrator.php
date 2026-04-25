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
        protected WhatsAppSessionInteractiveSequence $sessionInteractiveSequence,
        protected WhatsAppAiCustomerMessageLocalizationService $templateLocalization,
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

        if (!$this->isInboundMessageTypeTextLike($trigger->message_type)) {
            $recorder->step('branch.intent', 'Non-text inbound — canned message + buttons', 'ok', [
                'message_type' => $trigger->message_type,
            ]);
            $outId = $this->sendNonTextInboundReply($phone, $recorder, $text);
            $recorder->finish('non_text_inbound', ['outbound_id' => $outId]);

            return;
        }

        if ($this->isGreetingHumanButtonTap($text)) {
            $recorder->step('branch.intent', 'Greeting button — talk to human', 'ok', []);
            $outId = $this->sendHumanHandoffReply($phone, $recorder, $text);
            $recorder->finish('human_handoff', ['outbound_id' => $outId]);

            return;
        }

        if ($this->isHumanHandoffIntent($text)) {
            $recorder->step('branch.intent', 'Detected human handoff intent', 'ok', []);
            $outId = $this->sendHumanHandoffReply($phone, $recorder, $text);
            $recorder->finish('human_handoff', ['outbound_id' => $outId]);

            return;
        }

        if ($this->shouldSendGreetingButtonsOnly($phone, $text)) {
            $recorder->step('branch.intent', 'First-message greeting + buttons', 'ok', []);
            $outId = $this->sendWelcomeWithButtons($phone, $recorder, $text);
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
        $system .= "\n\n## Reply language (non-negotiable)\n"
            ."- Customer-visible text must be in the **same language** as the customer's **latest** message.\n"
            .'- **Do not** include translations, bilingual lines, labels like "English:", or the same sentence repeated in another language.';
        $tools = $this->aiSettings->mergedToolDeclarations();
        if ($tools === []) {
            Log::info('WhatsApp AI: no tools exposed to Gemini (admin disabled all, or invalid config)');
        }

        $iter = 0;
        $finalText = '';
        $replyKind = 'gemini';
        $hadProductiveToolThisRun = false;
        $hadReportUnclearThisRun = false;
        $toolFinalizeSessionMeta = null;
        /** @var ?string Set when submit_my_booking_for_human_confirmation succeeds; used if the model omits the id. */
        $pendingBookingRequestId = null;
        $maxRounds = (int) config('whatsappmodule.ai_gemini_max_tool_rounds', 6);
        while ($iter < $maxRounds) {
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
            $toolFinalizeSessionMeta = null;
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
                if ($toolName === 'submit_my_booking_for_human_confirmation' && is_array($result)) {
                    if (!empty($result['ok']) && !empty($result['booking_id'])) {
                        $pendingBookingRequestId = (string) $result['booking_id'];
                    }
                }
                if (is_array($result)) {
                    if (!empty($result['orchestrator_finalize']['send_unclear_handoff_message'])) {
                        $toolFinalizeUnclear = true;
                    }
                    if (!empty($result['orchestrator_finalize']['send_exact_customer_text'])) {
                        $t = trim((string) $result['orchestrator_finalize']['send_exact_customer_text']);
                        if ($t !== '') {
                            $toolFinalizeExact = $t;
                            $sm = $result['orchestrator_finalize']['session_meta_buttons'] ?? null;
                            $toolFinalizeSessionMeta = is_array($sm) && $sm !== [] ? $sm : null;
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
                $finalText = $this->buildUnclearLimitHandoffCustomerMessage($text, $recorder);
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

        if (
            $pendingBookingRequestId !== null
            && $pendingBookingRequestId !== ''
            && $replyKind === 'gemini'
            && $finalText !== ''
            && stripos($finalText, $pendingBookingRequestId) === false
        ) {
            $finalText = rtrim($finalText)."\n\n*Booking request ID:* ".$pendingBookingRequestId;
            $finalText = $this->sanitizeCustomerReply($finalText);
            $recorder->step('orchestrator.booking_id', 'Appended booking request id (model omitted it)', 'ok', [
                'booking_id' => $pendingBookingRequestId,
            ]);
        }

        if ($hadProductiveToolThisRun && !$hadReportUnclearThisRun) {
            $this->resetConversationUnclearAttempts($phone);
        }

        if ($replyKind === 'tool_canned') {
            $finalText = $this->templateLocalization->localizeTemplate($finalText, $text, $recorder);
            $finalText = $this->sanitizeCustomerReply($finalText);
            if ($finalText === '') {
                $finalText = $this->fallbackCustomerMessage();
                $replyKind = 'fallback';
            }
        }

        if (
            $replyKind === 'tool_canned'
            && isset($toolFinalizeSessionMeta)
            && is_array($toolFinalizeSessionMeta)
            && $toolFinalizeSessionMeta !== []
        ) {
            $toolFinalizeSessionMeta = $this->templateLocalization->localizeMetaButtons(
                $toolFinalizeSessionMeta,
                $text,
                $recorder
            );
        }

        if ($finalText === '') {
            $finalText = $this->fallbackCustomerMessage();
            $replyKind = 'fallback';
        }

        if ($replyKind === 'unclear_handoff') {
            $this->resetConversationUnclearAttempts($phone);
        }

        $useSessionMeta = $replyKind === 'tool_canned'
            && is_array($toolFinalizeSessionMeta)
            && $toolFinalizeSessionMeta !== [];
        $persistBody = $finalText.($useSessionMeta ? "\n[Quick actions]" : '');
        $out = $this->messagePersistence->persistOutboundPlaceholder($phone, $persistBody, 'AI');
        $err = null;
        $graph = null;
        $waId = null;
        if (WhatsAppAiPlayground::skipCloudApi($phone)) {
            $recorder->step('whatsapp.send', 'Sandbox — WhatsApp Cloud API skipped (no Meta send)', 'ok', [
                'sandbox' => true,
                'reply_kind' => $replyKind,
                'interactive' => $useSessionMeta,
            ]);
            if ($useSessionMeta && is_array($toolFinalizeSessionMeta) && $toolFinalizeSessionMeta !== []) {
                WhatsAppAiPlayground::storeOutboundSnapshot($phone, $finalText, $toolFinalizeSessionMeta, null);
            } else {
                WhatsAppAiPlayground::storePlainOutbound($phone, $finalText);
            }
        } elseif ($useSessionMeta) {
            $seqErr = null;
            $ids = $this->sessionInteractiveSequence->send(
                $this->whatsAppCloud,
                $phone,
                $finalText,
                $toolFinalizeSessionMeta,
                $seqErr,
                null
            );
            $waId = $ids[0] ?? null;
            if ($seqErr) {
                $err = $seqErr;
            }
            $recorder->step('whatsapp.send', 'Send session buttons (template-style sequence) via WhatsApp Cloud API', $err ? 'fail' : 'ok', [
                'graph_message_id' => $waId,
                'error' => $err ? mb_substr($err, 0, 500) : null,
                'reply_kind' => $replyKind,
                'interactive' => $useSessionMeta,
            ]);
            if ($waId) {
                $this->messagePersistence->attachWaMessageId($out, $waId);
            }
        } else {
            $waId = $this->whatsAppCloud->sendText($phone, $finalText, $err, $graph);
            $recorder->step('whatsapp.send', 'Send text via WhatsApp Cloud API', $err ? 'fail' : 'ok', [
                'graph_message_id' => $waId,
                'error' => $err ? mb_substr($err, 0, 500) : null,
                'reply_kind' => $replyKind,
                'interactive' => $useSessionMeta,
            ]);
            if ($waId) {
                $this->messagePersistence->attachWaMessageId($out, $waId);
            }
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
        $turnLimit = (int) config('whatsappmodule.ai_gemini_context_turn_limit', 18);
        $charLimit = (int) config('whatsappmodule.ai_gemini_context_char_limit', 2200);
        $rows = WhatsAppMessage::query()
            ->where('phone', $phone)
            ->orderByDesc('id')
            ->limit($turnLimit)
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
                'parts' => [['text' => mb_substr($t, 0, $charLimit)]],
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
                    'parts' => [['text' => mb_substr($raw, 0, $charLimit)]],
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
        $t = $this->normalizeWhatsAppCustomerTextFormatting($t);
        $t = $this->stripDevanagariFromCustomerReply($t);
        $t = $this->stripArabicScriptFromCustomerReply($t);
        $t = $this->stripTranslationArtifacts($t);

        return mb_substr(trim($t), 0, 3800);
    }

    /**
     * Remove translation glosses models add: parentheses, slash-pairs, metadata lines.
     */
    private function stripTranslationArtifacts(string $text): string
    {
        $t = $this->stripTranslationMetadataLines($text);
        $lines = preg_split('/\r\n|\r|\n/', $t) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = $this->stripTrailingSlashOrPipeEnglishGlossFromLine($line);
            $line = $this->stripTrailingEnglishParentheticalGlossFromLine($line);
            $out[] = $line;
        }

        return implode("\n", $out);
    }

    /**
     * Drop whole lines like "English: …" or "Translation: …".
     */
    private function stripTranslationMetadataLines(string $text): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $out = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*(?:English|Translation|Translate|TL|Trans\.?)\s*[:：\-–—]\s*\S+/iu', $line)) {
                continue;
            }
            $out[] = $line;
        }

        return implode("\n", $out);
    }

    /**
     * "Roman line / Which service…" — keep left side only when the right side is an English gloss.
     */
    private function stripTrailingSlashOrPipeEnglishGlossFromLine(string $line): string
    {
        if (!preg_match('/^(.*?)\s+(?:\/|\|)\s+(.+)$/u', $line, $m)) {
            return $line;
        }
        $right = trim($m[2]);
        if ($right === '' || preg_match('/\d/', $right)) {
            return $line;
        }
        if (!$this->segmentLooksLikeEnglishTranslationGloss($right)) {
            return $line;
        }

        return rtrim($m[1]);
    }

    private function segmentLooksLikeEnglishTranslationGloss(string $segment): bool
    {
        $segment = trim($segment);
        if (mb_strlen($segment) < 8) {
            return false;
        }
        if ($this->parentheticalLooksLikeEnglishTranslationPhrase($segment)) {
            return true;
        }
        if (preg_match('/\?\s*$/', $segment) && str_word_count($segment) >= 3) {
            return $this->isMostlyAsciiLatinLetters($segment);
        }

        return false;
    }

    private function isMostlyAsciiLatinLetters(string $s): bool
    {
        $letterCount = preg_match_all('/\p{L}/u', $s) ?: 0;
        if ($letterCount < 6) {
            return false;
        }
        $latinAscii = preg_match_all('/[A-Za-z]/', $s) ?: 0;

        return ($latinAscii / $letterCount) >= 0.88;
    }

    private function stripTrailingEnglishParentheticalGlossFromLine(string $line): string
    {
        $s = $line;
        for ($i = 0; $i < 12; $i++) {
            if (!preg_match('/\s+\(([^)]*)\)\s*$/u', $s, $m)) {
                break;
            }
            if (!$this->isLikelyEnglishTranslationParenthetical($m[1])) {
                break;
            }
            $s = preg_replace('/\s+\([^)]*\)\s*$/u', '', $s) ?? $s;
        }

        return rtrim($s);
    }

    /**
     * True when (...) at end of line looks like an English gloss, not an id or short tag.
     * Roman Urdu also uses Latin letters, so we require English translation-like wording
     * (e.g. "Which service…") or a long English question duplicate, not just "Latin text".
     */
    private function isLikelyEnglishTranslationParenthetical(string $inner): bool
    {
        $inner = trim($inner);
        if ($inner === '') {
            return false;
        }
        if (mb_strlen($inner) < 10) {
            return false;
        }
        if (preg_match('/\d/', $inner)) {
            return false;
        }
        $letterCount = preg_match_all('/\p{L}/u', $inner) ?: 0;
        if ($letterCount < 8) {
            return false;
        }
        $latinAscii = preg_match_all('/[A-Za-z]/', $inner) ?: 0;
        if ($latinAscii / $letterCount < 0.88) {
            return false;
        }

        if ($this->parentheticalLooksLikeEnglishTranslationPhrase($inner)) {
            return true;
        }

        return (bool) (preg_match('/\?\s*$/', $inner) && str_word_count($inner) >= 3);
    }

    private function parentheticalLooksLikeEnglishTranslationPhrase(string $inner): bool
    {
        return (bool) preg_match(
            '/^\s*(?:
                which\b|what\b|when\b|where\b|who\b|whom\b|why\b|how\b|
                do\s+you\b|did\s+you\b|can\s+you\b|could\s+you\b|would\s+you\b|should\s+you\b|
                is\s+there\b|are\s+you\b|is\s+this\b|are\s+there\b|is\s+it\b|
                please\b|thank\s+you\b|thanks\b|sorry\b
            )/ix',
            $inner
        );
    }

    /**
     * Customer-facing AI text must stay in Roman script (English / Hinglish). Models sometimes emit Hindi/Devanagari.
     */
    private function stripDevanagariFromCustomerReply(string $text): string
    {
        $t = preg_replace('/\p{Devanagari}/u', '', $text) ?? $text;
        $t = preg_replace('/[ \t]+/', ' ', $t) ?? $t;
        $t = preg_replace("/\n{3,}/", "\n\n", $t) ?? $t;

        return trim($t);
    }

    /**
     * Models sometimes emit Arabic/Persian script (e.g. Kashmiri/Urdu style); customer copy must stay Roman (English/Hinglish).
     */
    private function stripArabicScriptFromCustomerReply(string $text): string
    {
        $t = preg_replace('/\p{Arabic}/u', '', $text) ?? $text;
        $t = preg_replace('/[ \t]+/', ' ', $t) ?? $t;
        $t = preg_replace("/\n{3,}/", "\n\n", $t) ?? $t;

        return trim($t);
    }

    /**
     * WhatsApp uses *bold* (single asterisk pairs), not Markdown **bold**. Models often emit **…**
     * or "*   *Label:*" bullets, which show stray asterisks. Normalize before send.
     */
    private function normalizeWhatsAppCustomerTextFormatting(string $text): string
    {
        $t = $text;
        // Markdown bold → WhatsApp bold
        $prev = null;
        while ($prev !== $t) {
            $prev = $t;
            $t = preg_replace('/\*\*([^*]+)\*\*/u', '*$1*', $t) ?? $t;
        }
        // Line starts with "* " used as bullet before another *bold* segment → hyphen list
        $t = preg_replace('/^\*\s+(?=\*)/m', '- ', $t) ?? $t;

        return $t;
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
        $t = preg_replace('/\s*\[act_[a-z0-9_]+\]/iu', '', $t) ?? $t;
        $t = preg_replace('/\s*\[sess_qr_\d+\]/iu', '', $t) ?? $t;
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

    /**
     * Customer-safe text when Gemini cannot return a usable reply. No delivery/technical/error wording.
     */
    private function fallbackCustomerMessage(): string
    {
        $p = $this->aiSettings->resolvedMessagePlaceholders();
        $brand = trim((string) ($p['brand'] ?? ''));
        if ($brand === '') {
            $brand = (string) config('app.name');
        }
        $phone = trim((string) ($p['phone'] ?? ''));

        if ($phone !== '') {
            return (string) __('whatsapp_ai.customer_fallback_with_phone', [
                'brand' => $brand,
                'phone' => $phone,
            ]);
        }

        return (string) __('whatsapp_ai.customer_fallback_plain', ['brand' => $brand]);
    }

    private function isGreetingHumanButtonTap(string $text): bool
    {
        return (bool) preg_match('/\[act_human\]\s*$/', trim($text));
    }

    private function isHumanHandoffIntent(string $text): bool
    {
        $t = mb_strtolower($text);
        $needles = [
            'human', 'agent', 'representative', 'operator', 'manager',
            'call me', 'phone call', 'speak to someone', 'real person', 'live agent',
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

    /**
     * Plain text and interactive (button/list) replies are handled by the main AI path.
     */
    private function isInboundMessageTypeTextLike(?string $messageType): bool
    {
        $mt = strtoupper(trim((string) $messageType));
        if ($mt === '') {
            return true;
        }

        return in_array($mt, ['TEXT', 'INTERACTIVE'], true);
    }

    /**
     * Image, audio, document, etc. — no Gemini; configurable body + session buttons (e.g. call + chat with agent).
     */
    private function sendNonTextInboundReply(string $phone, WhatsAppAiExecutionRecorder $recorder, string $lastUserText): int
    {
        $msg = $this->aiSettings->nonTextInboundMessageForCustomer($phone);
        $msg = $this->templateLocalization->localizeTemplate($msg, $lastUserText, $recorder);
        $msg = $this->sanitizeCustomerReply($msg);
        if ($msg === '') {
            $msg = $this->sanitizeCustomerReply(
                $this->aiSettings->mergeCustomerMessagePlaceholders(
                    $this->aiSettings->defaultNonTextInboundMessageTemplate(),
                    $phone
                )
            );
        }

        $meta = $this->aiSettings->metaButtonsForContext('non_text');
        $meta = $this->templateLocalization->localizeMetaButtons($meta, $lastUserText, $recorder);

        $persistBody = $msg.($meta !== [] ? "\n[Quick actions]" : '');
        $out = $this->messagePersistence->persistOutboundPlaceholder($phone, $persistBody, 'AI');
        $err = null;
        $waId = null;
        $qrIds = $this->nonTextQuickReplyPayloadIds($meta);

        if (WhatsAppAiPlayground::skipCloudApi($phone)) {
            $recorder->step('whatsapp.send', 'Sandbox — non-text inbound saved; WhatsApp Cloud API skipped', 'ok', [
                'sandbox' => true,
            ]);
            if ($meta === []) {
                WhatsAppAiPlayground::storePlainOutbound($phone, $msg);
            } else {
                WhatsAppAiPlayground::storeOutboundSnapshot($phone, $msg, $meta, $qrIds);
            }
        } elseif ($meta === []) {
            $waId = $this->whatsAppCloud->sendText($phone, $msg, $err);
            $recorder->step('whatsapp.send', 'Send non-text inbound (text only)', $err ? 'fail' : 'ok', [
                'graph_message_id' => $waId,
                'error' => $err ? mb_substr($err, 0, 500) : null,
            ]);
            if ($waId) {
                $this->messagePersistence->attachWaMessageId($out, $waId);
            }
        } else {
            $ids = $this->sessionInteractiveSequence->send(
                $this->whatsAppCloud,
                $phone,
                $msg,
                $meta,
                $err,
                $qrIds
            );
            $waId = $ids[0] ?? null;
            $recorder->step('whatsapp.send', 'Send non-text inbound (session buttons)', $err ? 'fail' : 'ok', [
                'graph_message_id' => $waId,
                'error' => $err ? mb_substr($err, 0, 500) : null,
            ]);
            if ($waId) {
                $this->messagePersistence->attachWaMessageId($out, $waId);
            }
        }

        return (int) $out->id;
    }

    /**
     * First quick reply uses act_human so tapping "Chat with Agent" triggers the same path as the greeting human button.
     *
     * @param  array<int, array<string, mixed>>  $meta
     * @return list<string>|null
     */
    private function nonTextQuickReplyPayloadIds(array $meta): ?array
    {
        $ids = [];
        $qrIndex = 0;
        foreach ($meta as $b) {
            if (strtoupper((string) ($b['type'] ?? '')) !== 'QUICK_REPLY') {
                continue;
            }
            $qrIndex++;
            $ids[] = $qrIndex === 1 ? 'act_human' : 'sess_qr_'.$qrIndex;
        }

        return $ids === [] ? null : $ids;
    }

    private function sendWelcomeWithButtons(string $phone, WhatsAppAiExecutionRecorder $recorder, string $lastUserText): int
    {
        $body = $this->aiSettings->resolvedGreetingMessage($phone);
        // Do not run Gemini rewrite on the greeting body: models often return a partial rewrite
        // (truncated mid-paragraph) while quick-reply labels stay short and safe to localize.
        $body = $this->sanitizeCustomerReply($body);
        $meta = $this->aiSettings->metaButtonsForContext('greeting');
        $meta = $this->templateLocalization->localizeMetaButtons($meta, $lastUserText, $recorder);
        $note = $meta !== [] ? "\n[Quick actions]" : '';
        $out = $this->messagePersistence->persistOutboundPlaceholder($phone, $body.$note, 'AI');
        $err = null;
        $waId = null;
        $greetingQrIds = $this->aiSettings->greetingQuickReplyPayloadIdsForMeta($meta);

        if (WhatsAppAiPlayground::skipCloudApi($phone)) {
            $recorder->step('whatsapp.send', 'Sandbox — greeting saved; WhatsApp Cloud API skipped', 'ok', ['sandbox' => true]);
            if ($meta === []) {
                WhatsAppAiPlayground::storePlainOutbound($phone, $body);
            } else {
                WhatsAppAiPlayground::storeOutboundSnapshot($phone, $body, $meta, $greetingQrIds);
            }
        } elseif ($meta === []) {
            $waId = $this->whatsAppCloud->sendText($phone, $body, $err);
            $recorder->step('whatsapp.send', 'Send greeting text', $err ? 'fail' : 'ok', [
                'graph_message_id' => $waId,
                'error' => $err ? mb_substr($err, 0, 500) : null,
            ]);
            if ($waId) {
                $this->messagePersistence->attachWaMessageId($out, $waId);
            }
        } else {
            $ids = $this->sessionInteractiveSequence->send(
                $this->whatsAppCloud,
                $phone,
                $body,
                $meta,
                $err,
                $greetingQrIds
            );
            $waId = $ids[0] ?? null;
            $recorder->step('whatsapp.send', 'Send greeting (template-style session buttons)', $err ? 'fail' : 'ok', [
                'graph_message_id' => $waId,
                'error' => $err ? mb_substr($err, 0, 500) : null,
            ]);
            if ($waId) {
                $this->messagePersistence->attachWaMessageId($out, $waId);
            }
        }

        return (int) $out->id;
    }

    private function sendHumanHandoffReply(string $phone, WhatsAppAiExecutionRecorder $recorder, string $lastUserText): int
    {
        WhatsAppUser::markHumanSupportRequested($phone);

        $inHours = $this->workHours->isWithinSupportHours();
        $schedule = $this->workHours->humanReadableSchedule();
        $msg = $this->aiSettings->handoffMessageForCustomer($inHours);
        $msg = $this->templateLocalization->localizeTemplate($msg, $lastUserText, $recorder);
        $msg = $this->sanitizeCustomerReply($msg);
        $meta = $this->aiSettings->metaButtonsForContext($inHours ? 'handoff_in' : 'handoff_out');
        $meta = $this->templateLocalization->localizeMetaButtons($meta, $lastUserText, $recorder);

        $recorder->step('handoff.context', 'Support hours check', 'ok', [
            'in_hours' => $inHours,
            'schedule' => $schedule,
        ]);

        $persistBody = $msg.($meta !== [] ? "\n[Quick actions]" : '');
        $out = $this->messagePersistence->persistOutboundPlaceholder($phone, $persistBody, 'AI');
        $err = null;
        $waId = null;
        if (WhatsAppAiPlayground::skipCloudApi($phone)) {
            $recorder->step('whatsapp.send', 'Sandbox — handoff saved; WhatsApp Cloud API skipped', 'ok', ['sandbox' => true]);
            if ($meta === []) {
                WhatsAppAiPlayground::storePlainOutbound($phone, $msg);
            } else {
                WhatsAppAiPlayground::storeOutboundSnapshot($phone, $msg, $meta, null);
            }
        } elseif ($meta === []) {
            $waId = $this->whatsAppCloud->sendText($phone, $msg, $err);
            $recorder->step('whatsapp.send', 'Send human handoff text', $err ? 'fail' : 'ok', [
                'graph_message_id' => $waId,
                'error' => $err ? mb_substr($err, 0, 500) : null,
            ]);
            if ($waId) {
                $this->messagePersistence->attachWaMessageId($out, $waId);
            }
        } else {
            $ids = $this->sessionInteractiveSequence->send($this->whatsAppCloud, $phone, $msg, $meta, $err, null);
            $waId = $ids[0] ?? null;
            $recorder->step('whatsapp.send', 'Send human handoff (template-style session buttons)', $err ? 'fail' : 'ok', [
                'graph_message_id' => $waId,
                'error' => $err ? mb_substr($err, 0, 500) : null,
            ]);
            if ($waId) {
                $this->messagePersistence->attachWaMessageId($out, $waId);
            }
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

    private function buildUnclearLimitHandoffCustomerMessage(string $lastCustomerText, WhatsAppAiExecutionRecorder $recorder): string
    {
        $p = $this->aiSettings->resolvedMessagePlaceholders();
        $schedule = trim((string) ($p['schedule'] ?? ''));
        $displayPhone = trim((string) ($p['phone'] ?? ''));

        $supportLine = 'For more details, please reach out to our support team';
        if ($displayPhone !== '') {
            $supportLine .= " at {$displayPhone}";
        }
        if ($schedule !== '') {
            $supportLine .= " (we're usually available {$schedule})";
        }
        $supportLine .= '.';

        $msg = "Sorry — I'm still not able to understand what you need from this message, or I don't have that information here.\n\n"
            .$supportLine
            ."\n\nIs there anything else I can help you with today?";

        $msg = $this->templateLocalization->localizeTemplate($msg, $lastCustomerText, $recorder);

        return $this->sanitizeCustomerReply($msg);
    }
}
