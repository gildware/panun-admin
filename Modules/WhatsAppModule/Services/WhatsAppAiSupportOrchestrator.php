<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Log;
use Modules\WhatsAppModule\Entities\WhatsAppAiExecution;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

class WhatsAppAiSupportOrchestrator
{
    public function __construct(
        protected WhatsAppGeminiSupportClient $gemini,
        protected WhatsAppAiToolExecutor $toolExecutor,
        protected WhatsAppCloudService $whatsAppCloud,
        protected WhatsAppMessagePersistenceService $messagePersistence,
        protected WhatsAppSupportWorkHours $workHours,
        protected WhatsAppPublicCatalogService $catalog,
        protected WhatsAppAiSettingsService $aiSettings
    ) {}

    public function handleInboundMessageId(int $messageId, WhatsAppAiExecutionRecorder $recorder): void
    {
        if (!config('whatsappmodule.ai_support_enabled')) {
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
        $tools = $this->aiSettings->mergedToolDeclarations();
        if ($tools === []) {
            Log::info('WhatsApp AI: no tools exposed to Gemini (admin disabled all, or invalid config)');
        }

        $iter = 0;
        $finalText = '';
        $replyKind = 'gemini';
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
            foreach ($calls as $c) {
                $recorder->step('tool.call', 'Tool: ' . $c['name'], 'info', [
                    'args' => $this->truncateForLog($c['args'] ?? []),
                ]);
                $result = $this->toolExecutor->execute($c['name'], $c['args'], $phone);
                $recorder->step('tool.result', 'Tool result: ' . $c['name'], 'ok', [
                    'result' => $this->truncateForLog($result),
                ]);
                $userParts[] = [
                    'functionResponse' => [
                        'name' => $c['name'],
                        'response' => $result,
                    ],
                ];
            }
            $contents[] = ['role' => 'user', 'parts' => $userParts];
        }

        if ($finalText === '') {
            $finalText = $this->fallbackCustomerMessage();
            $replyKind = 'fallback';
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

        $outcome = $replyKind === 'fallback' ? 'gemini_fallback' : 'gemini_reply';
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

        return mb_substr(trim($t), 0, 3800);
    }

    private function fallbackCustomerMessage(): string
    {
        $phone = (string) config('whatsappmodule.support_phone_display');

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

        foreach ($needles as $n) {
            if (str_contains($t, $n)) {
                return true;
            }
        }

        return (bool) preg_match('/\b(talk|speak|connect)\s+(to|with)\s+(a\s+)?(human|person|agent)\b/i', $text);
    }

    private function shouldSendGreetingButtonsOnly(string $phone, string $text): bool
    {
        if (!config('whatsappmodule.ai_greeting_buttons')) {
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
        $snap = $this->catalog->buildPublicSnapshot();
        $brand = (string) ($snap['company'] ?? WhatsAppAiPromptBuilder::resolveBrandName());
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
        $displayPhone = (string) config('whatsappmodule.support_phone_display');

        if ($inHours) {
            $msg = "We're connecting you with our team.\n\n"
                . "Someone will pick up this chat during support hours ({$schedule}).\n";
            if ($displayPhone !== '') {
                $msg .= "\nYou can also call: {$displayPhone}";
            }
        } else {
            $msg = "Our live team is currently outside support hours.\n\n"
                . "We're available {$schedule}.\n";
            if ($displayPhone !== '') {
                $msg .= "\nLeave your message here or call {$displayPhone} and we'll get back to you.";
            } else {
                $msg .= "\nLeave your message here and we'll reply when we're back.";
            }
        }

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
}
