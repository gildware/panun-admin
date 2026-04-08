<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\DB;
use Modules\WhatsAppModule\Entities\WhatsAppAiExecution;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\WhatsAppModule\Jobs\ProcessWhatsAppAiSupportJob;

/**
 * Runs one inbound message through the real AI pipeline for a sandbox phone (no WhatsApp Cloud send).
 */
final class WhatsAppAiPlaygroundRunner
{
    public function __construct(
        protected WhatsAppMessagePersistenceService $messagePersistence,
        protected WhatsAppAiRuntimeResolver $aiRuntime
    ) {}

    /**
     * @return array{
     *   ok: bool,
     *   error?: string,
     *   inbound_message_id?: int,
     *   outbound_message_id?: int|null,
     *   reply_text?: string,
     *   execution_id?: int|null,
     *   execution_outcome?: string|null,
     *   phone?: string
     * }
     */
    public function runCustomerText(string $text, ?string $phone = null): array
    {
        $phone = $phone !== null && trim($phone) !== '' ? trim($phone) : WhatsAppAiPlayground::defaultSandboxPhone();
        if (! WhatsAppAiPlayground::skipCloudApi($phone)) {
            return [
                'ok' => false,
                'error' => 'Playground only allows sandbox phone keys (e.g. AI_TEST_SANDBOX or AI_TEST_*).',
            ];
        }

        if (! $this->aiRuntime->aiSupportEnabled()) {
            return ['ok' => false, 'error' => 'WhatsApp AI support is disabled in configuration.'];
        }

        if ((string) config('services.gemini.api_key') === '') {
            return ['ok' => false, 'error' => 'GEMINI_API_KEY is not set — add it to .env to run the playground.'];
        }

        $text = trim($text);
        if ($text === '') {
            return ['ok' => false, 'error' => 'Message text is empty.'];
        }

        $user = WhatsAppUser::firstOrNew(['phone' => $phone]);
        $user->handled_by = 'AI';
        if (! $user->exists) {
            $user->name = 'Playground tester';
        }
        $user->save();

        $inbound = $this->messagePersistence->persist([
            'phone' => $phone,
            'direction' => 'IN',
            'message_type' => 'TEXT',
            'message_text' => mb_substr($text, 0, 4000),
        ]);

        try {
            // Playground must run inline so the HTTP request can return the AI reply.
            ProcessWhatsAppAiSupportJob::dispatchSync($inbound->id);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'inbound_message_id' => (int) $inbound->id];
        }

        $execution = WhatsAppAiExecution::query()
            ->where('trigger_whatsapp_message_id', $inbound->id)
            ->orderByDesc('id')
            ->first();

        $outbound = WhatsAppMessage::query()
            ->where('phone', $phone)
            ->where('direction', 'OUT')
            ->orderByDesc('id')
            ->first();

        $outcome = $execution ? (string) $execution->outcome : null;
        $playgroundWarning = null;
        if ($outcome === 'gemini_fallback') {
            $playgroundWarning = (string) __('whatsapp_ai.playground_gemini_fallback_notice');
        }

        return [
            'ok' => true,
            'phone' => $phone,
            'inbound_message_id' => (int) $inbound->id,
            'outbound_message_id' => $outbound ? (int) $outbound->id : null,
            'reply_text' => $outbound ? WhatsAppAiPlayground::stripPersistedQuickActionsMarker((string) $outbound->message_text) : '',
            'execution_id' => $execution ? (int) $execution->id : null,
            'execution_outcome' => $outcome,
            'playground_warning' => $playgroundWarning,
            'interactive' => WhatsAppAiPlayground::getOutboundSnapshot($phone),
        ];
    }

    /**
     * @return array{ok: bool, error?: string, phone?: string, messages?: list<array<string, mixed>>, last_interactive?: array|null}
     */
    public function getThread(?string $phone = null): array
    {
        $phone = $phone !== null && trim($phone) !== '' ? trim($phone) : WhatsAppAiPlayground::defaultSandboxPhone();
        if (! WhatsAppAiPlayground::skipCloudApi($phone)) {
            return [
                'ok' => false,
                'error' => 'Playground only allows sandbox phone keys (e.g. AI_TEST_SANDBOX or AI_TEST_*).',
            ];
        }

        $rows = WhatsAppMessage::query()
            ->where('phone', $phone)
            ->orderBy('id')
            ->get(['id', 'direction', 'message_text', 'created_at']);

        $messages = $rows->map(function (WhatsAppMessage $m) {
            $raw = (string) $m->message_text;

            return [
                'id' => (int) $m->id,
                'direction' => (string) $m->direction,
                'text' => WhatsAppAiPlayground::stripPersistedQuickActionsMarker($raw),
                'created_at' => $m->created_at?->toIso8601String(),
            ];
        })->values()->all();

        return [
            'ok' => true,
            'phone' => $phone,
            'messages' => $messages,
            'last_interactive' => WhatsAppAiPlayground::getOutboundSnapshot($phone),
        ];
    }

    /**
     * Delete all messages and related sandbox rows for the default playground phone (optional cleanup).
     */
    public function resetSandboxThread(?string $phone = null): array
    {
        $phone = $phone !== null && trim($phone) !== '' ? trim($phone) : WhatsAppAiPlayground::defaultSandboxPhone();
        if (! WhatsAppAiPlayground::skipCloudApi($phone)) {
            return ['ok' => false, 'error' => 'Not a sandbox phone.'];
        }

        DB::transaction(function () use ($phone) {
            $ids = WhatsAppMessage::query()->where('phone', $phone)->pluck('id');
            if ($ids->isNotEmpty()) {
                WhatsAppAiExecution::query()
                    ->whereIn('trigger_whatsapp_message_id', $ids->all())
                    ->delete();
            }
            WhatsAppMessage::query()->where('phone', $phone)->delete();
        });

        WhatsAppAiPlayground::clearOutboundSnapshot($phone);

        return ['ok' => true, 'phone' => $phone];
    }
}
