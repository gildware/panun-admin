<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class PeopleSalaryStructure extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'effective_from',
        'basic',
        'hra',
        'special_allowance',
        'pf_employee',
        'pf_employer',
        'professional_tax',
        'tds',
        'other_deduction',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gross(): float
    {
        return round((float) $this->basic + (float) $this->hra + (float) $this->special_allowance, 2);
    }
}
