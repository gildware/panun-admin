<?php

namespace Modules\TaskBoardModule\Services;

use Modules\TaskBoardModule\Entities\TaskActivityLog;
use Modules\TaskBoardModule\Entities\TaskTicket;
use Throwable;

class TaskActivityLogger
{
    public function log(
        string $action,
        ?TaskTicket $ticket = null,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $actorId = null,
    ): ?TaskActivityLog {
        try {
            return TaskActivityLog::query()->create([
                'ticket_id' => $ticket?->id,
                'actor_id' => $actorId ?? auth()->id(),
                'action' => $action,
                'subject_type' => $subjectType ?? ($ticket ? TaskTicket::class : null),
                'subject_id' => $subjectId ?? $ticket?->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
