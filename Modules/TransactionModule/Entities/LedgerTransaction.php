<?php

namespace Modules\TransactionModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class LedgerTransaction extends Model
{
    use HasUuid;

    public const TYPE_IN = 'IN';
    public const TYPE_OUT = 'OUT';

    public const REASON_REFUND = 'refund';
    public const REASON_PROVIDER_PAYOUT = 'provider_payout';

    public const RECEIVED_BY_COMPANY = 'company';
    public const RECEIVED_BY_PROVIDER = 'provider';

    protected $fillable = [
        'amount',
        'type',
        'transaction_id',
        'booking_id',
        'booking_repeat_id',
        'booking_partial_payment_id',
        'provider_id',
        'payment_method',
        'reason',
        'date',
        'received_by',
        'reference_note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'date' => 'date',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function repeat(): BelongsTo
    {
        return $this->belongsTo(BookingRepeat::class, 'booking_repeat_id');
    }

    public function bookingPartialPayment(): BelongsTo
    {
        return $this->belongsTo(BookingPartialPayment::class, 'booking_partial_payment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Who recorded this row in admin UIs / exports. Prefer stored user; else infer from linked partial (e.g. migration backfill).
     */
    public function resolvedEntryByLabel(): string
    {
        if ($this->creator) {
            $name = trim((string) ($this->creator->first_name ?? '') . ' ' . (string) ($this->creator->last_name ?? ''));
            if ($name !== '') {
                return $name;
            }
            if (!empty($this->creator->email)) {
                return (string) $this->creator->email;
            }
        }

        $partial = $this->bookingPartialPayment;
        if ($partial) {
            $paidWith = (string) ($partial->paid_with ?? '');

            return match ($paidWith) {
                'admin_entry' => translate('Admin'),
                'wallet', 'digital', 'offline_payment' => translate('Customer'),
                'cash_after_service' => translate('Booking Complete'),
                default => $paidWith !== '' ? str_replace('_', ' ', $paidWith) : '—',
            };
        }

        return '—';
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Offline method name from ledger reference (e.g. "QR Code UPI — UTR: …" from {@see AdminCompanyInflowPaymentService::buildOfflineReferenceNoteForLedger}).
     */
    public function offlineSubtypeLabelFromReferenceNote(): ?string
    {
        $ref = trim((string) ($this->reference_note ?? ''));
        if ($ref === '') {
            return null;
        }
        $lines = preg_split('/\R/u', $ref, 2);
        $firstLine = trim((string) ($lines[0] ?? ''));
        if ($firstLine === '') {
            return null;
        }
        if (preg_match('/^(.+?)\s*[—–]\s*(.+)$/u', $firstLine, $m)) {
            return trim($m[1]);
        }
        if (! str_contains($firstLine, ':')) {
            return $firstLine;
        }

        return null;
    }

    /**
     * Human-readable payment / flow label for ledger & transaction list UIs.
     */
    public function formatPaymentMethodForDisplay(): string
    {
        if ($this->type === self::TYPE_OUT) {
            if ($this->reason === self::REASON_REFUND) {
                return translate('Refund');
            }
            if ($this->reason === self::REASON_PROVIDER_PAYOUT) {
                return translate('Provider_payout');
            }

            return $this->reason ? ucwords(str_replace('_', ' ', (string) $this->reason)) : '—';
        }

        $pm = (string) ($this->payment_method ?? '');
        if ($pm === '') {
            return '—';
        }

        $tk = 'ledger_pm_' . $pm;
        $tr = translate($tk);
        $main = ($tr !== $tk) ? $tr : ucwords(str_replace('_', ' ', $pm));

        if ($this->type === self::TYPE_IN && in_array($pm, ['offline_payment', 'offline'], true)) {
            $sub = $this->offlineSubtypeLabelFromReferenceNote();
            if ($sub !== null && $sub !== '') {
                return $main . ' — ' . $sub;
            }
        }

        return $main;
    }

    public function scopeIn($query)
    {
        return $query->where('type', self::TYPE_IN);
    }

    public function scopeOut($query)
    {
        return $query->where('type', self::TYPE_OUT);
    }

    /**
     * Company-counterparty meaning for admin UIs (ledger = IN/OUT only).
     *
     * @return string customer_to_company|provider_to_company|company_to_customer|provider_to_customer|company_to_provider|unknown
     */
    public function counterpartyFlowKey(): string
    {
        if ($this->type === self::TYPE_IN) {
            $pm = (string) ($this->payment_method ?? '');
            if ($pm === 'collect_from_provider') {
                return 'provider_to_company';
            }
            // Collect-from-provider flow now stores the real inflow method (UPI, offline, etc.) via
            // {@see collectCashTransaction()} — those rows still have provider_id and no booking_id.
            if ($this->provider_id !== null && $this->booking_id === null) {
                return 'provider_to_company';
            }
            if ($this->received_by === self::RECEIVED_BY_COMPANY) {
                return 'customer_to_company';
            }

            return 'unknown';
        }

        if ($this->type === self::TYPE_OUT) {
            if ($this->reason === self::REASON_REFUND) {
                // e.g. reopen disputed refund: pool taken from provider-held funds vs company pool
                if ($this->received_by === self::RECEIVED_BY_PROVIDER) {
                    return 'provider_to_customer';
                }

                return 'company_to_customer';
            }
            if ($this->reason === self::REASON_PROVIDER_PAYOUT) {
                return 'company_to_provider';
            }

            return 'unknown';
        }

        return 'unknown';
    }

    /**
     * Ledger rows that involve the company as counterparty (IN/OUT only; no direct customer↔provider).
     *
     * Excludes: IN where the provider received the payment (customer→provider); OUT refunds funded from
     * the provider pool ({@see RECEIVED_BY_PROVIDER}) (provider→customer). Those are tracked on bookings /
     * provider accounts, not on the company ledger.
     */
    public function scopeWhereCompanyCounterpartyOnly($query)
    {
        return $query->excludingNonCompanyCounterpartyIn()
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('type', self::TYPE_IN)
                        ->where('received_by', self::RECEIVED_BY_COMPANY);
                })->orWhere(function ($q2) {
                    $q2->where('type', self::TYPE_OUT)
                        ->where('reason', self::REASON_PROVIDER_PAYOUT);
                })->orWhere(function ($q2) {
                    $q2->where('type', self::TYPE_OUT)
                        ->where('reason', self::REASON_REFUND)
                        ->where(function ($q3) {
                            $q3->where('received_by', self::RECEIVED_BY_COMPANY)
                                ->orWhereNull('received_by')
                                ->orWhere('received_by', '');
                        });
                });
            });
    }

    /**
     * Ledger is only for company ↔ customer or company ↔ provider.
     * Exclude legacy/wrong rows that represented customer → provider (now {@see Transaction::FLOW_NONE} on transactions).
     */
    public function scopeExcludingNonCompanyCounterpartyIn($query)
    {
        return $query->whereNot(function ($q) {
            $q->where('type', self::TYPE_IN)
                ->where('received_by', self::RECEIVED_BY_PROVIDER);
        });
    }
}
