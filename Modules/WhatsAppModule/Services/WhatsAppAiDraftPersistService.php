<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Log;
use Modules\WhatsAppModule\Entities\ProviderLead;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;
use Modules\WhatsAppModule\Entities\WhatsAppConversation;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

/**
 * Server-side safety net: after the customer-facing Gemini turn, persist a booking draft
 * (and mark the CRM lead as customer) whenever the thread shows they need a service —
 * even if Gemini only "noted" fields in chat and never called upsert_my_draft_booking.
 */
class WhatsAppAiDraftPersistService
{
    public function __construct(
        protected WhatsAppGeminiSupportClient $gemini,
        protected WhatsAppAiToolExecutor $toolExecutor,
    ) {}

    public function persistIfNeeded(string $phone, WhatsAppAiExecutionRecorder $recorder): void
    {
        if (!filter_var(config('whatsappmodule.ai_silent_draft_persist', true), FILTER_VALIDATE_BOOL)) {
            return;
        }

        try {
            $this->run($phone, $recorder);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp AI silent draft persist failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            $recorder->step('draft.persist', 'Silent draft persist failed', 'fail', [
                'error' => mb_substr($e->getMessage(), 0, 240),
            ]);
        }
    }

    private function run(string $phone, WhatsAppAiExecutionRecorder $recorder): void
    {
        $conv = WhatsAppConversation::query()->where('phone', $phone)->first();
        if (strtoupper((string) ($conv?->active_module ?? '')) === 'JOIN_PROVIDER') {
            $recorder->step('draft.persist', 'Skip — provider onboarding thread', 'skip', []);

            return;
        }

        $providerDraft = ProviderLead::query()
            ->where('phone', $phone)
            ->where('status', ProviderLead::STATUS_DRAFT)
            ->orderByDesc('id')
            ->first();
        if ($providerDraft) {
            $recorder->step('draft.persist', 'Skip — provider lead draft exists', 'skip', []);

            return;
        }

        $draft = $this->editableDraft($phone, $conv?->active_booking_id);
        $customerBlob = $this->customerTextBlob($phone);

        if (WhatsAppAiBookingIntentDetector::looksLikeProviderOnboarding($customerBlob)) {
            $recorder->step('draft.persist', 'Skip — provider onboarding wording', 'skip', []);

            return;
        }

        if ($draft === null && !WhatsAppAiBookingIntentDetector::looksLikeCustomerServiceNeed($customerBlob)) {
            $recorder->step('draft.persist', 'Skip — no customer service intent yet', 'skip', []);

            return;
        }

        $activeNonDraft = $this->activeNonDraftBooking($phone, $conv?->active_booking_id);
        if ($activeNonDraft && $draft === null) {
            $recorder->step('draft.persist', 'Skip — active booking already submitted', 'skip', [
                'booking_id' => $activeNonDraft->booking_id,
                'status' => $activeNonDraft->status,
            ]);

            return;
        }

        $upsertDecl = $this->upsertDeclaration();
        if ($upsertDecl === null) {
            $recorder->step('draft.persist', 'Skip — upsert tool not available', 'skip', []);

            return;
        }

        $profile = WhatsAppUser::query()->where('phone', $phone)->first();
        $system = $this->extractorSystemPrompt($profile?->name, $draft);
        $transcript = $this->threadTranscript($phone);
        $contents = [[
            'role' => 'user',
            'parts' => [['text' => $transcript !== '' ? $transcript : $customerBlob]],
        ]];

        $turn = $this->gemini->generateTurn(
            $system,
            $contents,
            [$upsertDecl],
            $recorder,
            null,
            512,
            null,
            [
                'toolCallingConfig' => [
                    'mode' => 'ANY',
                    'allowedFunctionNames' => ['upsert_my_draft_booking'],
                ],
                'temperature' => 0.0,
            ],
        );

        if (($turn['type'] ?? '') !== 'function_calls') {
            $recorder->step('draft.persist', 'Extractor did not call upsert', 'skip', [
                'turn_type' => $turn['type'] ?? null,
            ]);

            return;
        }

        $calls = $turn['calls'] ?? [];
        $saved = false;
        foreach ($calls as $c) {
            if ((string) ($c['name'] ?? '') !== 'upsert_my_draft_booking') {
                continue;
            }
            $args = is_array($c['args'] ?? null) ? $c['args'] : [];
            if ($draft && empty($args['booking_id'])) {
                $args['booking_id'] = $draft->booking_id;
            }
            $result = $this->toolExecutor->execute('upsert_my_draft_booking', $args, $phone);
            $recorder->step('draft.persist', 'Silent upsert_my_draft_booking', !empty($result['ok']) ? 'ok' : 'fail', [
                'args' => $this->truncate($args),
                'result' => $this->truncate($result),
            ]);
            $saved = $saved || !empty($result['ok']);
        }

        if (!$saved) {
            $recorder->step('draft.persist', 'Silent upsert did not save', 'skip', []);
        }
    }

    private function editableDraft(string $phone, ?string $activeBookingId): ?WhatsAppBooking
    {
        if ($activeBookingId) {
            $row = WhatsAppBooking::query()
                ->where('phone', $phone)
                ->where('booking_id', $activeBookingId)
                ->first();
            if ($row && $row->status === WhatsAppBooking::STATUS_DRAFT) {
                return $row;
            }
        }

        return WhatsAppBooking::query()
            ->where('phone', $phone)
            ->where('status', WhatsAppBooking::STATUS_DRAFT)
            ->orderByDesc('updated_at')
            ->first();
    }

    private function activeNonDraftBooking(string $phone, ?string $activeBookingId): ?WhatsAppBooking
    {
        if (!$activeBookingId) {
            return null;
        }

        $row = WhatsAppBooking::query()
            ->where('phone', $phone)
            ->where('booking_id', $activeBookingId)
            ->first();
        if (!$row || $row->status === WhatsAppBooking::STATUS_DRAFT) {
            return null;
        }

        return $row;
    }

    private function customerTextBlob(string $phone): string
    {
        $rows = WhatsAppMessage::query()
            ->where('phone', $phone)
            ->where('direction', 'IN')
            ->orderByDesc('id')
            ->limit(16)
            ->get(['message_text']);

        $parts = [];
        foreach ($rows->reverse() as $row) {
            $t = trim((string) $row->message_text);
            if ($t !== '') {
                $parts[] = $t;
            }
        }

        return implode("\n", $parts);
    }

    private function threadTranscript(string $phone): string
    {
        $rows = WhatsAppMessage::query()
            ->where('phone', $phone)
            ->orderByDesc('id')
            ->limit(24)
            ->get(['message_text', 'direction']);

        $lines = [];
        foreach ($rows->reverse() as $row) {
            $t = trim((string) $row->message_text);
            if ($t === '' || (str_starts_with($t, '[') && str_ends_with($t, ']'))) {
                continue;
            }
            $who = $row->direction === 'IN' ? 'Customer' : 'Assistant';
            $lines[] = $who.': '.mb_substr($t, 0, 800);
        }

        return implode("\n", $lines);
    }

    private function extractorSystemPrompt(?string $savedName, ?WhatsAppBooking $draft): string
    {
        $nameLine = trim((string) $savedName) !== ''
            ? 'Saved profile name (use as booking name unless the customer gave a different person name): '.trim((string) $savedName).'.'
            : 'No saved profile name. Only pass name if the customer clearly gave a person name — never a job type.';

        $draftLine = 'No draft yet — create one with whatever the customer already said.';
        if ($draft) {
            $draftLine = 'Existing DRAFT booking_id='.$draft->booking_id
                .' service='.trim((string) ($draft->service ?? 'empty'))
                .' address='.trim((string) ($draft->address ?? 'empty'))
                .' datetime='.($draft->prefered_datetime ? 'set' : 'empty')
                .'. Pass booking_id and only add/update fields the customer actually provided.';
        }

        return <<<PROMPT
You extract booking fields from a WhatsApp thread. You never talk to the customer.

{$nameLine}
{$draftLine}

You MUST call upsert_my_draft_booking with every field the CUSTOMER already provided:
- name: real person name if known
- service: the job they want (e.g. ceiling fan installation)
- address: full visit address if they gave one (house/landmark/area)
- preferred_datetime_text: only if they gave a real date/time. If they said they will tell later, omit it.
- service_description: extra job details if any

Partial drafts are required. Do not wait for date/time. Do not invent an address or date.
Saying "noted" in chat is not a save — this tool call is the save.
PROMPT;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function upsertDeclaration(): ?array
    {
        foreach (WhatsAppAiToolExecutor::functionDeclarations() as $decl) {
            if (($decl['name'] ?? '') === 'upsert_my_draft_booking') {
                return $decl;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function truncate(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $out[$k] = mb_substr($v, 0, 240);
            } elseif (is_scalar($v) || $v === null) {
                $out[$k] = $v;
            } else {
                $out[$k] = '[…]';
            }
        }

        return $out;
    }
}
