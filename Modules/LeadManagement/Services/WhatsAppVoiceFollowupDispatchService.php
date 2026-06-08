<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Collection;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupDispatch;

class WhatsAppVoiceFollowupDispatchService
{
    public function __construct(
        private readonly WhatsAppFollowupContextBuilder $contextBuilder,
        private readonly OmniDimensionService $omniDimension,
        private readonly VoiceBulkCallContactBuilder $bulkContactBuilder
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $candidates
     * @param  array<string, mixed>  $options
     * @return array{ok: bool, campaign_ids: array<int, int|string>, dispatched_count: int, message: string, error: ?string}
     */
    public function dispatchCandidates(Collection|array $candidates, array $options): array
    {
        if (!$this->omniDimension->isConfigured()) {
            return [
                'ok' => false,
                'campaign_ids' => [],
                'dispatched_count' => 0,
                'message' => translate('OmniDimension_is_not_configured'),
                'error' => 'not_configured',
            ];
        }

        $candidateList = $candidates instanceof Collection ? $candidates->values() : collect($candidates)->values();
        if ($candidateList->isEmpty()) {
            return [
                'ok' => false,
                'campaign_ids' => [],
                'dispatched_count' => 0,
                'message' => translate('Voice_bulk_no_valid_contacts'),
                'error' => 'no_contacts',
            ];
        }

        $groups = [];
        foreach ($candidateList as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $phone = (string) ($candidate['phone'] ?? '');
            if ($phone === '') {
                continue;
            }

            $leadType = (string) ($candidate['lead_type'] ?? Lead::TYPE_UNKNOWN);
            $phoneNumberId = $this->resolvePhoneNumberId($leadType, $options);
            if ($phoneNumberId === null) {
                continue;
            }

            $e164 = $this->omniDimension->normalizeToE164($phone);
            if ($e164 === null) {
                continue;
            }

            $built = (isset($candidate['call_context']) && is_array($candidate['call_context']) && $candidate['call_context'] !== [])
                ? [
                    'context' => $candidate['call_context'],
                    'lead_summary_preview' => (string) ($candidate['lead_summary_preview'] ?? ''),
                ]
                : $this->contextBuilder->buildForCandidate($candidate);
            $contact = array_merge(
                ['phone_number' => $e164],
                $built['context']
            );

            $groups[$phoneNumberId][] = [
                'contact' => $contact,
                'candidate' => array_merge($candidate, [
                    'call_context' => $built['context'],
                    'lead_summary_preview' => $built['lead_summary_preview'],
                ]),
                'e164' => $e164,
            ];
        }

        if ($groups === []) {
            return [
                'ok' => false,
                'campaign_ids' => [],
                'dispatched_count' => 0,
                'message' => translate('Voice_bulk_no_valid_contacts'),
                'error' => 'no_valid_groups',
            ];
        }

        $campaignName = trim((string) ($options['campaign_name'] ?? 'WhatsApp follow-up'));
        $source = (string) ($options['source'] ?? 'manual');
        $dispatchedBy = $options['dispatched_by'] ?? null;
        $automationRunId = isset($options['automation_run_id']) ? (int) $options['automation_run_id'] : null;
        $payloadOptions = array_merge([
            'send_option' => 'now',
            'enabled_reschedule_call' => false,
            'auto_retry' => false,
        ], $options);

        $campaignIds = [];
        $dispatchedCount = 0;
        $apiError = null;

        foreach ($groups as $phoneNumberId => $items) {
            $contactList = array_map(fn (array $row) => $row['contact'], $items);
            $payload = $this->bulkContactBuilder->buildApiPayload(
                $campaignName . ' #' . $phoneNumberId,
                (int) $phoneNumberId,
                $contactList,
                $payloadOptions
            );

            $result = $this->omniDimension->createBulkCall($payload, $apiError);
            if (!$result['ok']) {
                return [
                    'ok' => false,
                    'campaign_ids' => $campaignIds,
                    'dispatched_count' => $dispatchedCount,
                    'message' => translate('Voice_bulk_campaign_failed'),
                    'error' => $apiError ?? 'api_failed',
                ];
            }

            $campaignId = $result['campaign_id'];
            $status = $result['status'] ?? 'pending';
            if ($campaignId !== null) {
                $campaignIds[] = $campaignId;
            }

            foreach ($items as $row) {
                WhatsAppVoiceFollowupDispatch::create([
                    'wa_phone' => (string) ($row['candidate']['phone'] ?? ''),
                    'to_number_e164' => $row['e164'],
                    'lead_id' => $row['candidate']['lead_id'] ?? null,
                    'lead_type' => $row['candidate']['lead_type'] ?? null,
                    'omnidim_campaign_id' => $campaignId,
                    'call_status' => $status,
                    'call_context' => $row['candidate']['call_context'] ?? [],
                    'source' => $source,
                    'dispatched_by' => $dispatchedBy,
                    'automation_run_id' => $automationRunId,
                ]);
                $dispatchedCount++;
            }
        }

        $message = translate('Voice_bulk_campaign_created_successfully');
        if ($campaignIds !== []) {
            $message .= ' #' . implode(', #', $campaignIds);
        }

        return [
            'ok' => true,
            'campaign_ids' => $campaignIds,
            'dispatched_count' => $dispatchedCount,
            'message' => $message,
            'error' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function resolvePhoneNumberId(string $leadType, array $options): ?int
    {
        $key = match ($leadType) {
            Lead::TYPE_PROVIDER => 'phone_number_id_provider',
            Lead::TYPE_CUSTOMER => 'phone_number_id_customer',
            default => 'phone_number_id_unknown',
        };

        $fromOptions = (int) ($options[$key] ?? 0);
        if ($fromOptions > 0) {
            return $fromOptions;
        }

        $configKey = match ($leadType) {
            Lead::TYPE_PROVIDER => 'followup_phone_number_provider',
            Lead::TYPE_CUSTOMER => 'followup_phone_number_customer',
            default => 'followup_phone_number_unknown',
        };

        $fromConfig = (int) config('services.omnidimension.' . $configKey, 0);
        if ($fromConfig <= 0 && $key === 'phone_number_id_unknown') {
            $fromConfig = (int) config('services.omnidimension.followup_phone_number_customer', 0);
        }

        return $fromConfig > 0 ? $fromConfig : null;
    }
}
