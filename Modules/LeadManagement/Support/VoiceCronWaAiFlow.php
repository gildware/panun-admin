<?php

namespace Modules\LeadManagement\Support;

class VoiceCronWaAiFlow
{
    public const CUSTOMER_BOOKING_SUBMITTED = 'customer_booking_submitted';

    public const PROVIDER_LEAD_SUBMITTED = 'provider_lead_submitted';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::CUSTOMER_BOOKING_SUBMITTED => translate('Voice_cron_wa_ai_flow_customer_booking_submitted'),
            self::PROVIDER_LEAD_SUBMITTED => translate('Voice_cron_wa_ai_flow_provider_lead_submitted'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    public static function label(string $key): string
    {
        return self::options()[$key] ?? $key;
    }

    public static function candidateFlag(string $key): string
    {
        return match ($key) {
            self::CUSTOMER_BOOKING_SUBMITTED => 'wa_ai_customer_booking_submitted',
            self::PROVIDER_LEAD_SUBMITTED => 'wa_ai_provider_lead_submitted',
            default => '',
        };
    }
}
