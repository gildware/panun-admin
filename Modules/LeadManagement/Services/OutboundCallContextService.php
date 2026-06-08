<?php

namespace Modules\LeadManagement\Services;

class OutboundCallContextService
{
    public const CALL_REASON_WHATSAPP_FOLLOWUP = 'WHATSAPP_FOLLOWUP';

    public const CALL_REASON_FUTURE_CUSTOMER_FOLLOWUP = 'FUTURE_CUSTOMER_FOLLOWUP';

    public const CALL_REASON_INBOUND_CALL = 'INBOUND_CALL';

    public const CALL_REASON_PROVIDER_CALLBACK = 'PROVIDER_CALLBACK';

    /**
     * Keys sent to OmniDimension as call_context (order preserved for readability).
     *
     * @var list<string>
     */
    public const CONTEXT_KEYS = [
        'customer_name',
        'call_reason',
        'lead_status',
        'lead_summary',
        'service_category',
        'service_details',
        'district',
        'area',
        'preferred_date',
        'preferred_time',
        'notes',
    ];

    /**
     * @return list<string>
     */
    public static function callReasons(): array
    {
        return [
            self::CALL_REASON_WHATSAPP_FOLLOWUP,
            self::CALL_REASON_FUTURE_CUSTOMER_FOLLOWUP,
            self::CALL_REASON_INBOUND_CALL,
            self::CALL_REASON_PROVIDER_CALLBACK,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function callReasonLabels(): array
    {
        return [
            self::CALL_REASON_WHATSAPP_FOLLOWUP => 'WhatsApp follow-up',
            self::CALL_REASON_FUTURE_CUSTOMER_FOLLOWUP => 'Future customer follow-up',
            self::CALL_REASON_INBOUND_CALL => 'Inbound call',
            self::CALL_REASON_PROVIDER_CALLBACK => 'Provider callback',
        ];
    }

    public static function callReasonBadgeClass(string $reason): string
    {
        return match (strtoupper(trim($reason))) {
            self::CALL_REASON_WHATSAPP_FOLLOWUP => 'voice-call-reason-badge--whatsapp',
            self::CALL_REASON_FUTURE_CUSTOMER_FOLLOWUP => 'voice-call-reason-badge--future-customer',
            self::CALL_REASON_INBOUND_CALL => 'voice-call-reason-badge--inbound',
            self::CALL_REASON_PROVIDER_CALLBACK => 'voice-call-reason-badge--provider-callback',
            default => 'voice-call-reason-badge--default',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function callReasonBadgeClasses(): array
    {
        $classes = [];
        foreach (self::callReasons() as $reason) {
            $classes[$reason] = self::callReasonBadgeClass($reason);
        }

        return $classes;
    }

    /**
     * Build call_context for OmniDimension — only non-empty values are included.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function build(array $input): array
    {
        $context = [];

        foreach (self::CONTEXT_KEYS as $key) {
            $value = trim((string) ($input[$key] ?? ''));
            if ($value !== '') {
                $context[$key] = $value;
            }
        }

        return $context;
    }

    /**
     * @return array<string, string>
     */
    public function validationRules(): array
    {
        $reasons = implode(',', self::callReasons());

        return [
            'customer_name' => 'required|string|max:255',
            'call_reason' => 'nullable|string|in:' . $reasons,
            'lead_status' => 'nullable|string|max:64',
            'lead_summary' => 'nullable|string|max:2000',
            'service_category' => 'nullable|string|max:255',
            'service_details' => 'nullable|string|max:2000',
            'district' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'preferred_date' => 'nullable|string|max:64',
            'preferred_time' => 'nullable|string|max:64',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
