<?php

namespace Modules\CallCenterModule\Transformers;

use Modules\CallCenterModule\Entities\Call;

class CallTransformer
{
    public function transformFull(Call $call): array
    {
        return [
            'id' => $call->id,
            'external_id' => $call->external_id,
            'customer_id' => $call->customer_profile_id,
            'direction' => $call->direction,
            'status' => $call->status,
            'from_number' => $call->from_number,
            'to_number' => $call->to_number,
            'agent_external_id' => $call->agent_external_id,
            'agent_name' => $call->agent_name,
            'duration_seconds' => $call->duration_seconds,
            'disposition' => $call->disposition,
            'started_at' => $call->started_at?->utc()->toIso8601String(),
            'answered_at' => $call->answered_at?->utc()->toIso8601String(),
            'ended_at' => $call->ended_at?->utc()->toIso8601String(),
            'created_at' => $call->created_at?->utc()->toIso8601String(),
        ];
    }

    public function transformCreated(Call $call): array
    {
        return [
            'id' => $call->id,
            'external_id' => $call->external_id,
            'customer_id' => $call->customer_profile_id,
            'status' => $call->status,
            'created_at' => $call->created_at?->utc()->toIso8601String(),
        ];
    }
}
