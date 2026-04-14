<?php

namespace Modules\BookingModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\TransactionModule\Entities\LedgerTransaction;

class BookingPartialPayment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'booking_id',
        'paid_with',
        'transaction_id',
        'paid_amount',
        'due_amount',
        'received_by', // company | provider - who received this payment
        'loss_allocation_provider',
        'loss_allocation_company',
    ];

    protected static function newFactory()
    {
        return \Modules\BookingModule\Database\factories\BookingPartialPaymentFactory::new();
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function ledgerTransactions(): HasMany
    {
        return $this->hasMany(LedgerTransaction::class, 'booking_partial_payment_id');
    }

    /**
     * Admin list label: prefer linked ledger row (includes offline sub-method from reference), else paid_with + booking offline method name.
     */
    public function paymentMethodLabelForAdmin(?Booking $booking = null): string
    {
        $ledger = null;
        if ($this->relationLoaded('ledgerTransactions')) {
            $ledger = $this->ledgerTransactions
                ->where('type', LedgerTransaction::TYPE_IN)
                ->sortByDesc('created_at')
                ->first();
        } else {
            $ledger = $this->ledgerTransactions()
                ->where('type', LedgerTransaction::TYPE_IN)
                ->orderByDesc('created_at')
                ->first();
        }
        if ($ledger instanceof LedgerTransaction) {
            return $ledger->formatPaymentMethodForDisplay();
        }

        $base = $this->formatPaidWithForDisplay();
        $booking = $booking ?? $this->booking;
        if ($booking && ($this->paid_with ?? '') === 'offline') {
            if (! $booking->relationLoaded('booking_offline_payments')) {
                $booking->loadMissing('booking_offline_payments');
            }
            $mn = trim((string) ($booking->booking_offline_payments->first()?->method_name ?? ''));
            if ($mn !== '') {
                return $base . ' — ' . $mn;
            }
        }

        return $base;
    }

    /**
     * Human-readable channel for admin payment history (aligns with ledger_pm_* labels where possible).
     */
    public function formatPaidWithForDisplay(): string
    {
        $pw = (string) ($this->paid_with ?? '');
        if ($pw === '') {
            return '—';
        }

        $legacyKeys = [
            'wallet' => 'ledger_pm_wallet_payment',
            'digital' => 'ledger_pm_digital_payment',
            'offline' => 'ledger_pm_offline_payment',
        ];
        if (isset($legacyKeys[$pw])) {
            return translate($legacyKeys[$pw]);
        }

        $normalized = str_replace('-', '_', $pw);
        $tk = 'ledger_pm_' . $normalized;
        $tr = translate($tk);
        if ($tr !== $tk) {
            return $tr;
        }

        return ucwords(str_replace('_', ' ', $normalized));
    }
}
