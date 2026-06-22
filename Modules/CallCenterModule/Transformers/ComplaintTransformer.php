<?php

namespace Modules\CallCenterModule\Transformers;

use Modules\ProviderManagement\Entities\CustomerIncident;

class ComplaintTransformer
{
    public function transform(CustomerIncident $incident): array
    {
        $incident->loadMissing(['booking']);

        return [
            'id' => $incident->id,
            'complaint_ref' => 'CMP-' . str_pad((string) $incident->id, 4, '0', STR_PAD_LEFT),
            'status' => 'open',
            'subject' => $incident->incident_type === 'COMPLAINT' ? 'Complaint' : 'Incident',
            'description' => $incident->notes,
            'priority' => 'normal',
            'created_at' => $incident->created_at?->utc()->toIso8601String(),
            'resolved_at' => null,
        ];
    }
}
