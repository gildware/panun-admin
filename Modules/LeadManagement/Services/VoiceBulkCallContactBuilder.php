<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Facades\Storage;

class VoiceBulkCallContactBuilder
{
    public function __construct(
        private readonly OmniDimensionService $omniDimension
    ) {}

    /**
     * @return array<int, array{name: string, phone: string, category_name: string, context: array<string, string>}>
     */
    public function parseContactsCsv(?string $relativePath): array
    {
        if (!$relativePath || !Storage::disk('local')->exists($relativePath)) {
            return [];
        }

        $full = Storage::disk('local')->path($relativePath);
        if (!is_readable($full)) {
            return [];
        }

        $fh = fopen($full, 'r');
        if ($fh === false) {
            return [];
        }

        $header = fgetcsv($fh);
        if ($header === false) {
            fclose($fh);

            return [];
        }

        $map = $this->normalizeCsvHeader($header);
        $rows = [];

        while (($row = fgetcsv($fh)) !== false) {
            $parsed = $this->parseCsvRow($row, $map);
            if ($parsed !== null) {
                $rows[] = $parsed;
            }
        }

        fclose($fh);

        return $this->uniqueByPhone($rows);
    }

    /**
     * @param  array<int, array{name: string, phone: string, category_name?: string, context?: array<string, string>}>  $recipients
     * @param  array<string, string>  $sharedContext
     * @return array<int, array<string, string>>
     */
    public function buildContactList(array $recipients, array $sharedContext = []): array
    {
        $contacts = [];

        foreach ($recipients as $recipient) {
            $e164 = $this->omniDimension->normalizeToE164((string) ($recipient['phone'] ?? ''));
            if ($e164 === null) {
                continue;
            }

            $contact = ['phone_number' => $e164];

            $rowContext = is_array($recipient['context'] ?? null) ? $recipient['context'] : [];

            $name = trim((string) ($recipient['name'] ?? ''));
            if ($name !== '' && !isset($rowContext['customer_name'])) {
                $contact['customer_name'] = $name;
            }

            $categoryName = trim((string) ($recipient['category_name'] ?? ''));
            if ($categoryName !== '' && !isset($sharedContext['service_category']) && !isset($rowContext['service_category'])) {
                $contact['service_category'] = $categoryName;
            }

            foreach ($sharedContext as $key => $value) {
                $text = trim((string) $value);
                if ($text !== '' && !isset($contact[$key])) {
                    $contact[$key] = $text;
                }
            }

            foreach ($rowContext as $key => $value) {
                $text = trim((string) $value);
                if ($text !== '') {
                    $contact[$key] = $text;
                }
            }

            $contacts[] = $contact;
        }

        return $contacts;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildApiPayload(
        string $campaignName,
        int $phoneNumberId,
        array $contactList,
        array $input
    ): array {
        $payload = [
            'name' => $campaignName,
            'phone_number_id' => (string) $phoneNumberId,
            'contact_list' => $contactList,
        ];

        $isScheduled = ($input['send_option'] ?? 'now') === 'schedule';
        $payload['is_scheduled'] = $isScheduled;

        if ($isScheduled && !empty($input['scheduled_at'])) {
            $payload['scheduled_datetime'] = \Carbon\Carbon::parse($input['scheduled_at'])
                ->format('Y-m-d H:i:s');
            $payload['timezone'] = trim((string) ($input['timezone'] ?? 'UTC')) ?: 'UTC';
        }

        $concurrent = (int) ($input['concurrent_call_limit'] ?? 1);
        if ($concurrent > 0) {
            $payload['concurrent_call_limit'] = min(20, max(1, $concurrent));
        }

        $payload['enabled_reschedule_call'] = !empty($input['enabled_reschedule_call']);

        if (!empty($input['auto_retry'])) {
            $payload['retry_config'] = [
                'auto_retry' => true,
                'auto_retry_schedule' => (string) ($input['auto_retry_schedule'] ?? 'next_day'),
                'retry_limit' => min(5, max(1, (int) ($input['retry_limit'] ?? 2))),
            ];

            if (($input['auto_retry_schedule'] ?? '') === 'scheduled_time') {
                $payload['retry_config']['retry_schedule_days'] = max(0, (int) ($input['retry_schedule_days'] ?? 0));
                $payload['retry_config']['retry_schedule_hours'] = max(0, (int) ($input['retry_schedule_hours'] ?? 0));
            }
        }

        return $payload;
    }

    /**
     * @param  array<int, string|null>  $header
     * @return array{phone: ?int, name: ?int, context: array<string, int>}
     */
    private function normalizeCsvHeader(array $header): array
    {
        $phone = null;
        $name = null;
        $context = [];

        foreach ($header as $index => $column) {
            $key = strtolower(trim((string) $column));
            $key = preg_replace('/[\s\-]+/', '_', $key) ?? $key;

            if (in_array($key, ['phone', 'phone_number', 'mobile', 'contact'], true)) {
                $phone = $index;
            } elseif (in_array($key, ['name', 'customer_name', 'contact_name'], true)) {
                $name = $index;
            } elseif (in_array($key, OutboundCallContextService::CONTEXT_KEYS, true)) {
                $context[$key] = $index;
            }
        }

        return ['phone' => $phone, 'name' => $name, 'context' => $context];
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array{phone: ?int, name: ?int, context: array<string, int>}  $map
     * @return array{name: string, phone: string, category_name: string, context: array<string, string>}|null
     */
    private function parseCsvRow(array $row, array $map): ?array
    {
        if ($map['phone'] === null) {
            $phone = trim((string) ($row[1] ?? ''));
            $name = trim((string) ($row[0] ?? ''));
        } else {
            $phone = trim((string) ($row[$map['phone']] ?? ''));
            $name = $map['name'] !== null ? trim((string) ($row[$map['name']] ?? '')) : '';
        }

        if ($phone === '') {
            return null;
        }

        $context = [];
        foreach ($map['context'] as $key => $index) {
            $value = trim((string) ($row[$index] ?? ''));
            if ($value !== '') {
                $context[$key] = $value;
            }
        }

        return [
            'name' => $name,
            'phone' => $phone,
            'category_name' => '',
            'context' => $context,
        ];
    }

    /**
     * @param  array<int, array{name: string, phone: string}>  $rows
     * @return array<int, array{name: string, phone: string, category_name: string, context: array<string, string>}>
     */
    private function uniqueByPhone(array $rows): array
    {
        $seen = [];
        $out = [];

        foreach ($rows as $row) {
            $normalized = app(\Modules\WhatsAppModule\Services\WhatsAppCloudService::class)
                ->normalizeRecipientPhone((string) ($row['phone'] ?? ''));
            if ($normalized === null || isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;
            $row['phone'] = $normalized;
            $out[] = $row;
        }

        return $out;
    }
}
