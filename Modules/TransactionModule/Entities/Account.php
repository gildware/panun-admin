<?php

namespace Modules\TransactionModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ProviderManagement\Entities\Provider;

class Account extends Model
{
    use HasFactory;
    use HasUuid;

    protected $casts = [
        'balance_pending' => 'float',
        'received_balance' => 'float',
        'account_payable' => 'float',
        'account_receivable' => 'float',
        'total_withdrawn' => 'float',
    ];

    protected $fillable = ['user_id','balance_pending','received_balance','account_payable','account_receivable','total_withdrawn'];

    /**
     * Skip the cash-limit check while a settlement revert is rewriting balances.
     * The revert applies the check once, after the ledger row is gone.
     */
    public static bool $deferCashLimitSuspension = false;

    protected static function newFactory()
    {
        return \Modules\TransactionModule\Database\factories\AccountFactory::new();
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {

        });

        self::created(function ($model) {

        });

        self::updating(function ($model) {
            // ... code here
        });

        self::updated(function ($model) {
            if (self::$deferCashLimitSuspension) {
                return;
            }
            if ($model->isDirty('account_payable') || $model->isDirty('account_receivable')) {
                $provider = Provider::where('user_id', $model->user_id)->first();
                if ($provider) {
                    provider_apply_cash_limit_suspension_for_provider($provider);
                }
            }
        });

        self::deleting(function ($model) {
            // ... code here
        });

        self::deleted(function ($model) {
            // ... code here
        });
    }
}
