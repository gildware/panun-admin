<?php

namespace Modules\WhatsAppModule\Services;

use Modules\WhatsAppModule\Entities\WhatsAppAiExecution;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Throwable;

/**
 * Persists a linear timeline of steps for one AI pipeline run (n8n-style execution log).
 */
final class WhatsAppAiExecutionRecorder
{
    private bool $closed = false;

    public function __construct(
        private WhatsAppAiExecution $execution
    ) {}

    public static function begin(int $inboundMessageId): self
    {
        $msg = WhatsAppMessage::query()->find($inboundMessageId);

        $execution = WhatsAppAiExecution::query()->create([
            'trigger_whatsapp_message_id' => $inboundMessageId,
            'phone' => $msg?->phone ?? '',
            'status' => WhatsAppAiExecution::STATUS_RUNNING,
            'steps' => [],
            'meta' => [
                'queue_default' => config('queue.default'),
                'ai_dispatch_sync' => (bool) config('whatsappmodule.ai_dispatch_sync', true),
                'gemini_model' => config('whatsappmodule.gemini_model'),
            ],
            'started_at' => now(),
        ]);

        $recorder = new self($execution);
        $recorder->pushStep('pipeline.job', 'AI job started', 'ok', [
            'inbound_message_id' => $inboundMessageId,
            'phone' => $msg?->phone,
        ]);

        return $recorder;
    }

    public function execution(): WhatsAppAiExecution
    {
        return $this->execution;
    }

    public function step(string $key, string $label, string $status, array $detail = []): void
    {
        if ($this->closed) {
            return;
        }
        $this->pushStep($key, $label, $status, $detail);
    }

    /**
     * @param  array{summary?: string, outbound_id?: int|null, error?: string|null, status?: string}  $opts
     */
    public function finish(string $outcome, array $opts = []): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;

        $status = $opts['status'] ?? WhatsAppAiExecution::STATUS_COMPLETED;
        $summary = $opts['summary'] ?? $this->guessSummary($outcome, $opts);

        $this->execution->update([
            'status' => $status,
            'outcome' => $outcome,
            'summary' => mb_substr($summary, 0, 500),
            'outbound_whatsapp_message_id' => $opts['outbound_id'] ?? null,
            'error_message' => isset($opts['error']) ? mb_substr((string) $opts['error'], 0, 65000) : null,
            'finished_at' => now(),
        ]);
    }

    public function fail(Throwable|string $e): void
    {
        if ($this->closed) {
            return;
        }
        $message = $e instanceof Throwable ? $e->getMessage() : (string) $e;
        $this->step('pipeline.error', 'Unhandled error', 'fail', [
            'message' => mb_substr($message, 0, 2000),
            'class' => $e instanceof Throwable ? $e::class : null,
        ]);
        $this->finish('failed', [
            'status' => WhatsAppAiExecution::STATUS_FAILED,
            'error' => $message,
            'summary' => 'Failed: ' . mb_substr($message, 0, 200),
        ]);
    }

    private function guessSummary(string $outcome, array $opts): string
    {
        if (!empty($opts['summary'])) {
            return (string) $opts['summary'];
        }

        return match ($outcome) {
            'human_handoff' => 'Human handoff message sent',
            'greeting_buttons' => 'Welcome + quick-reply buttons sent',
            'gemini_reply' => 'Gemini reply sent to customer',
            'gemini_fallback' => 'Fallback message sent (Gemini unavailable)',
            'no_context' => 'Skipped — no usable chat context',
            'skipped_not_latest' => 'Skipped — newer inbound message exists',
            'skipped_handled_by' => 'Skipped — chat not assigned to AI',
            'skipped_trigger_invalid' => 'Skipped — invalid trigger message',
            'skipped_ai_disabled' => 'Skipped — AI disabled in config',
            'failed' => 'Pipeline failed',
            default => $outcome,
        };
    }

    private function pushStep(string $key, string $label, string $status, array $detail): void
    {
        $this->execution->refresh();
        $steps = $this->execution->steps ?? [];
        $steps[] = [
            't' => now()->toIso8601String(),
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'detail' => $this->truncateDetail($detail),
        ];
        $this->execution->steps = $steps;
        $this->execution->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function truncateDetail(array $detail, int $maxJson = 6000): array
    {
        $encoded = json_encode($detail, JSON_UNESCAPED_UNICODE);
        if ($encoded !== false && strlen($encoded) <= $maxJson) {
            return $detail;
        }

        return [
            '_truncated' => true,
            'preview' => mb_substr($encoded !== false ? $encoded : '', 0, $maxJson),
        ];
    }
}
