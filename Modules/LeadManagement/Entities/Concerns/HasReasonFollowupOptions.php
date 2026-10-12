<?php

namespace Modules\LeadManagement\Entities\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\LeadManagement\Entities\LeadReasonFollowupOption;

trait HasReasonFollowupOptions
{
    public function options(): HasMany
    {
        return $this->hasMany(LeadReasonFollowupOption::class, 'reason_id')
            ->where('reason_type', $this->followupReasonType())
            ->orderBy('name');
    }

    abstract public function followupReasonType(): string;
}
