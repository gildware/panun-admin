<?php

namespace Modules\CallCenterModule\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\CallCenterModule\Entities\Agent;
use Modules\CallCenterModule\Support\RespondsWithCallCenterApi;

class AgentController extends Controller
{
    use RespondsWithCallCenterApi;

    public function show(int $id): JsonResponse
    {
        $agent = Agent::query()->find($id);
        if (!$agent) {
            return $this->notFound('agent_not_found', 'Agent not found');
        }

        return $this->ok($this->transformAgent($agent));
    }

    public function showByExternalId(string $externalId): JsonResponse
    {
        $agent = Agent::query()->where('external_id', $externalId)->first();
        if (!$agent) {
            return $this->notFound('agent_not_found', 'Agent not found');
        }

        return $this->ok($this->transformAgent($agent));
    }

    private function transformAgent(Agent $agent): array
    {
        return [
            'id' => $agent->id,
            'external_id' => $agent->external_id,
            'name' => $agent->name,
            'email' => $agent->email,
        ];
    }
}
