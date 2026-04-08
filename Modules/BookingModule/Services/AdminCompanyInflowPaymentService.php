<?php

namespace Modules\BookingModule\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\PaymentModule\Entities\OfflinePayment;

/**
 * Admin UI catalog for “how this money was actually collected” (advance on create, add payment, collect from provider).
 *
 * Cash after service is not offered here: it describes paying at job completion, not a receipt channel for money already taken.
 * Bookings with no advance still default to cash_after_service in {@see BookingController::store()} when advance amount is zero.
 */
final class AdminCompanyInflowPaymentService
{
    /**
     * @return list<array{id: string, label: string, options: list<array<string, mixed>>}>
     */
    public static function advanceMethodGroups(): array
    {
        $groups = [];

        $digitalOptions = [];
        if ((int) (optional(business_config('digital_payment', 'service_setup'))->live_values ?? 0) === 1 && Schema::hasTable('addon_settings')) {
            $methods = DB::table('addon_settings')->where('settings_type', 'payment_config')->get();
            $env = env('APP_ENV') === 'live' ? 'live' : 'test';
            $credentials = $env . '_values';
            foreach ($methods as $method) {
                $gatewayKey = strtolower((string) $method->key_name);
                if (in_array($gatewayKey, ['wallet_payment', 'offline_payment', 'cash_after_service', 'cash_payment'], true)) {
                    continue;
                }
                if ((int) ($method->is_active ?? 0) !== 1) {
                    continue;
                }
                $credentialsData = json_decode($method->$credentials ?? '{}');
                $additionalData = json_decode($method->additional_data ?? '{}');
                if (! $credentialsData || (int) ($credentialsData->status ?? 0) !== 1) {
                    continue;
                }
                $title = $additionalData->gateway_title ?? ucwords(str_replace('_', ' ', (string) $method->key_name));
                $digitalOptions[] = [
                    'key' => (string) $method->key_name,
                    'label' => (string) $title,
                    'kind' => 'digital',
                    'fields' => [[
                        'name' => 'advance_transaction_id',
                        'input_name' => 'advance_transaction_id',
                        'label' => translate('Transaction_Reference_ID'),
                        'placeholder' => translate('Enter_payment_reference_or_transaction_id'),
                        'required' => true,
                        'type' => 'text',
                    ]],
                ];
            }
            usort($digitalOptions, static fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']));
        }

        if ((int) (optional(business_config('wallet_payment', 'service_setup'))->live_values ?? 0) === 1) {
            $digitalOptions[] = [
                'key' => 'wallet_payment',
                'label' => translate('wallet_payment'),
                'kind' => 'digital',
                'fields' => [[
                    'name' => 'advance_transaction_id',
                    'input_name' => 'advance_transaction_id',
                    'label' => translate('Transaction_Reference_ID'),
                    'placeholder' => translate('Enter_payment_reference_or_transaction_id'),
                    'required' => true,
                    'type' => 'text',
                ]],
            ];
        }

        if ($digitalOptions !== []) {
            $onlyWallet = count($digitalOptions) === 1 && ($digitalOptions[0]['key'] ?? '') === 'wallet_payment';
            $groups[] = [
                'id' => 'digital',
                'label' => $onlyWallet ? translate('wallet_payment') : translate('Digital_payment'),
                'options' => $digitalOptions,
            ];
        }

        if ((int) (optional(business_config('offline_payment', 'service_setup'))->live_values ?? 0) === 1 && Schema::hasTable('offline_payments')) {
            $offlineRows = OfflinePayment::query()
                ->where(function ($q): void {
                    $q->where('is_active', 1)->orWhere('is_active', true);
                })
                ->orderBy('method_name')
                ->get(['id', 'method_name', 'customer_information']);
            $offlineOptions = [];
            foreach ($offlineRows as $row) {
                $fields = [];
                foreach ($row->customer_information ?? [] as $c) {
                    if (! is_array($c)) {
                        continue;
                    }
                    if (($c['field_name'] ?? '') === 'payment_note') {
                        continue;
                    }
                    $fn = trim((string) ($c['field_name'] ?? ''));
                    if ($fn === '') {
                        continue;
                    }
                    $fields[] = [
                        'name' => $fn,
                        'input_name' => 'advance_method_fields[' . $fn . ']',
                        'label' => ucwords(str_replace('_', ' ', $fn)),
                        'placeholder' => (string) ($c['placeholder'] ?? ''),
                        'required' => ! empty($c['is_required']),
                        'type' => 'text',
                    ];
                }
                $offlineOptions[] = [
                    'key' => 'offline:' . $row->id,
                    'label' => $row->method_name,
                    'kind' => 'offline',
                    'fields' => $fields,
                ];
            }
            if ($offlineOptions !== []) {
                $groups[] = [
                    'id' => 'offline',
                    'label' => translate('offline_payment'),
                    'options' => $offlineOptions,
                ];
            }
        }

        return $groups;
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @return list<string>
     */
    public static function collectKeysFromGroups(array $groups): array
    {
        $keys = [];
        foreach ($groups as $group) {
            foreach ($group['options'] ?? [] as $opt) {
                if (! empty($opt['key'])) {
                    $keys[] = (string) $opt['key'];
                }
            }
        }

        return $keys;
    }

    public static function allowedAdvanceMethodKeys(): array
    {
        return self::collectKeysFromGroups(self::advanceMethodGroups());
    }

    public static function classifyChoiceKind(string $choice): string
    {
        if ($choice === 'cash_after_service') {
            return 'cas';
        }
        if ($choice === 'offline' || str_starts_with($choice, 'offline:')) {
            return 'offline';
        }

        return 'digital';
    }

    public static function truncateBookingTransactionIdField(string $s): string
    {
        $s = trim($s);

        return mb_strlen($s) > 191 ? mb_substr($s, 0, 188) . '...' : $s;
    }

    public static function truncateLedgerTransactionIdField(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }

        return mb_strlen($s) > 100 ? mb_substr($s, 0, 97) . '...' : $s;
    }

    /**
     * Prefer a field that looks like a transaction / UTR / reference id; else first non-empty customer field.
     */
    public static function extractOfflinePrimaryTransactionId(OfflinePayment $method, array $submitted): string
    {
        $candidates = [];
        foreach ($method->customer_information ?? [] as $c) {
            if (! is_array($c) || ($c['field_name'] ?? '') === 'payment_note') {
                continue;
            }
            $fn = (string) ($c['field_name'] ?? '');
            if ($fn === '') {
                continue;
            }
            $val = trim((string) ($submitted[$fn] ?? ''));
            if ($val === '') {
                continue;
            }
            $lower = strtolower($fn);
            if (preg_match('/transaction|trx|utr|reference|ref_no|ref_id|payment_id|receipt|voucher/i', $lower)) {
                return $val;
            }
            $candidates[] = $val;
        }

        return $candidates[0] ?? '';
    }

    public static function extractTransactionIdForStorageOnly(string $advanceChoice, Request $request): string
    {
        $kind = self::classifyChoiceKind($advanceChoice);

        if ($kind === 'digital' || $kind === 'cas') {
            return self::truncateBookingTransactionIdField(trim((string) $request->input('advance_transaction_id', '')));
        }

        if ($kind !== 'offline' || ! str_starts_with($advanceChoice, 'offline:')) {
            return '';
        }

        if (! Schema::hasTable('offline_payments')) {
            return '';
        }

        $offlineId = substr($advanceChoice, strlen('offline:'));
        $method = OfflinePayment::query()
            ->whereKey($offlineId)
            ->where('is_active', 1)
            ->first();
        if (! $method) {
            return '';
        }

        return self::truncateBookingTransactionIdField(
            self::extractOfflinePrimaryTransactionId($method, (array) $request->input('advance_method_fields', []))
        );
    }

    public static function buildOfflineReferenceNoteForLedger(string $advanceChoice, Request $request): ?string
    {
        if (! str_starts_with($advanceChoice, 'offline:') || ! Schema::hasTable('offline_payments')) {
            return null;
        }

        $offlineId = substr($advanceChoice, strlen('offline:'));
        $method = OfflinePayment::query()
            ->whereKey($offlineId)
            ->where('is_active', 1)
            ->first();
        if (! $method) {
            return null;
        }

        $submitted = (array) $request->input('advance_method_fields', []);
        $parts = [];
        foreach ($method->customer_information ?? [] as $c) {
            if (! is_array($c) || ($c['field_name'] ?? '') === 'payment_note') {
                continue;
            }
            $fn = (string) ($c['field_name'] ?? '');
            if ($fn === '') {
                continue;
            }
            $val = trim((string) ($submitted[$fn] ?? ''));
            if ($val !== '') {
                $parts[] = ucwords(str_replace('_', ' ', $fn)) . ': ' . $val;
            }
        }

        $summary = implode(' · ', array_filter($parts));

        $out = $summary !== '' ? ($method->method_name . ' — ' . $summary) : (string) $method->method_name;

        return $out !== '' ? $out : null;
    }

    public static function mapPartialPaidWithToLedgerPaymentMethod(string $partialPaidWith): string
    {
        return match ($partialPaidWith) {
            'offline' => 'offline_payment',
            'cash_after_service' => 'cash_after_service',
            'wallet_payment' => 'wallet_payment',
            default => $partialPaidWith,
        };
    }

    public static function ledgerPaymentMethodFromAdvanceChoice(string $choice): string
    {
        $isOfflineChoice = $choice === 'offline' || str_starts_with($choice, 'offline:');
        $partialPaidWith = match (true) {
            $choice === 'cash_after_service' => 'cash_after_service',
            $isOfflineChoice => 'offline',
            default => $choice,
        };

        return self::mapPartialPaidWithToLedgerPaymentMethod($partialPaidWith);
    }

    public static function mergeReferenceNotes(?string $offlineOrPrimary, ?string $userNote): ?string
    {
        $userNote = trim((string) $userNote);
        $a = trim((string) ($offlineOrPrimary ?? ''));
        if ($a === '' && $userNote === '') {
            return null;
        }
        if ($a === '') {
            return $userNote;
        }
        if ($userNote === '') {
            return $a;
        }

        return $a . "\n\n" . $userNote;
    }

    /**
     * When admins put UTR / reference only in "Reference note", copy it into empty required offline fields
     * (e.g. Transaction Id) so validation and ledger extraction succeed.
     */
    public static function backfillOfflineRequiredFromCompanyInflowNote(Request $request, OfflinePayment $method): void
    {
        $note = trim((string) $request->input('company_inflow_note', ''));
        if ($note === '') {
            return;
        }

        $requiredFns = [];
        foreach ($method->customer_information ?? [] as $c) {
            if (! is_array($c) || ($c['field_name'] ?? '') === 'payment_note') {
                continue;
            }
            $fn = trim((string) ($c['field_name'] ?? ''));
            if ($fn === '' || (int) ($c['is_required'] ?? 0) !== 1) {
                continue;
            }
            $requiredFns[] = $fn;
        }
        if ($requiredFns === []) {
            return;
        }

        $merged = (array) $request->input('advance_method_fields', []);
        $isTxnLike = static function (string $fieldName): bool {
            $lower = strtolower($fieldName);

            return (bool) preg_match('/transaction|trx|utr|reference|ref_no|ref_id|payment_id|receipt|voucher|txn|utr_no|upi/i', $lower);
        };

        $changed = false;
        foreach ($requiredFns as $fn) {
            if (trim((string) ($merged[$fn] ?? '')) !== '') {
                continue;
            }
            if ($isTxnLike($fn)) {
                $merged[$fn] = $note;
                $changed = true;
                break;
            }
        }
        if (! $changed && count($requiredFns) === 1) {
            $only = $requiredFns[0];
            if (trim((string) ($merged[$only] ?? '')) === '') {
                $merged[$only] = $note;
                $changed = true;
            }
        }
        if ($changed) {
            $request->merge(['advance_method_fields' => $merged]);
        }
    }

    /**
     * Validates transaction reference / offline field requirements (same rules as booking create advance).
     */
    public static function validateAdvanceFollowUp(Request $request, string $choice): void
    {
        $allowed = self::allowedAdvanceMethodKeys();
        if ($allowed === []) {
            throw ValidationException::withMessages([
                'advance_payment_method' => [translate('No_active_payment_methods_for_advance')],
            ]);
        }

        if ($choice === '' || ! in_array($choice, $allowed, true)) {
            throw ValidationException::withMessages([
                'advance_payment_method' => [translate('The selected advance payment method is invalid.')],
            ]);
        }

        $kind = self::classifyChoiceKind($choice);
        if ($kind === 'digital') {
            $tid = trim((string) $request->input('advance_transaction_id', ''));
            $note = trim((string) $request->input('company_inflow_note', ''));
            if ($tid === '' && $note !== '') {
                $request->merge(['advance_transaction_id' => $note]);
            }
            Validator::make($request->all(), [
                'advance_transaction_id' => ['required', 'string', 'max:191'],
            ], [
                'advance_transaction_id.required' => translate('Transaction_Reference_ID') . ': ' . translate('This field is required.'),
            ])->validate();
        }

        if ($kind === 'offline' && str_starts_with($choice, 'offline:')) {
            $offlineId = substr($choice, strlen('offline:'));
            $method = OfflinePayment::query()
                ->whereKey($offlineId)
                ->where('is_active', 1)
                ->first();
            if (! $method) {
                throw ValidationException::withMessages([
                    'advance_payment_method' => [translate('Invalid_offline_payment_method')],
                ]);
            }

            self::backfillOfflineRequiredFromCompanyInflowNote($request, $method);

            $rules = [];
            $messages = [];
            $attributes = [];
            foreach ($method->customer_information ?? [] as $c) {
                if (! is_array($c) || ($c['field_name'] ?? '') === 'payment_note') {
                    continue;
                }
                $fn = trim((string) ($c['field_name'] ?? ''));
                if ($fn === '' || (int) ($c['is_required'] ?? 0) !== 1) {
                    continue;
                }
                $key = 'advance_method_fields.' . $fn;
                $rules[$key] = ['required', 'string', 'max:2000'];
                $pretty = ucwords(str_replace('_', ' ', $fn));
                $attributes[$key] = $pretty;
                $messages[$key . '.required'] = $pretty . ': ' . translate('This field is required.');
            }
            if ($rules !== []) {
                Validator::make($request->all(), $rules, $messages, $attributes)->validate();
            }
        }
    }

    /**
     * @return array{ledger_payment_method: string, partial_transaction_id: ?string, ledger_transaction_id: ?string, ledger_reference_note: ?string}
     */
    public static function resolveLedgerPayloadForCompanyInflow(Request $request, string $choice, string $userNoteKey = 'company_inflow_note'): array
    {
        $txn = self::extractTransactionIdForStorageOnly($choice, $request);
        $ledgerTxn = self::truncateLedgerTransactionIdField($txn);
        $offlineNote = str_starts_with($choice, 'offline:')
            ? self::buildOfflineReferenceNoteForLedger($choice, $request)
            : null;
        $userNote = (string) $request->input($userNoteKey, '');
        $ref = self::mergeReferenceNotes($offlineNote, $userNote);

        return [
            'ledger_payment_method' => self::ledgerPaymentMethodFromAdvanceChoice($choice),
            'partial_transaction_id' => $txn !== '' ? $txn : null,
            'ledger_transaction_id' => $ledgerTxn !== '' ? $ledgerTxn : null,
            'ledger_reference_note' => $ref,
        ];
    }

    /**
     * @return array<string, array{kind: string, fields: list<array<string, mixed>>}>
     */
    public static function fieldConfigMapFromGroups(array $groups): array
    {
        $map = [];
        foreach ($groups as $group) {
            foreach ($group['options'] ?? [] as $opt) {
                if (empty($opt['key'])) {
                    continue;
                }
                $map[(string) $opt['key']] = [
                    'kind' => (string) ($opt['kind'] ?? ''),
                    'fields' => $opt['fields'] ?? [],
                ];
            }
        }

        return $map;
    }
}
