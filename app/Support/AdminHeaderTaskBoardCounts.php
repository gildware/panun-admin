<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\TaskBoardModule\Entities\TaskTicket;
use Modules\UserManagement\Entities\User;

final class AdminHeaderTaskBoardCounts
{
    private const CACHE_TTL_SECONDS = 30;

    /** @var array<int, string> */
    private const TODO_COLUMN_NAMES = ['to do', 'todo', 'to-do'];

    /** @var array<int, string> */
    private const IN_PROGRESS_COLUMN_NAMES = ['in progress', 'in-progress', 'inprogress'];

    /**
     * @return array{todo: int, in_progress: int, total: int}
     */
    public static function assignedCounts(?User $user): array
    {
        if (! $user || ! in_array($user->user_type, ADMIN_USER_TYPES, true)) {
            return self::emptyCounts();
        }

        try {
            return Cache::remember(
                'admin_header_task_board_assigned:'.$user->id,
                self::CACHE_TTL_SECONDS,
                fn () => self::computeAssignedCounts((string) $user->id)
            );
        } catch (\Throwable $e) {
            report($e);

            return self::computeAssignedCounts((string) $user->id);
        }
    }

    public static function forgetForUser(int|string $userId): void
    {
        try {
            Cache::forget('admin_header_task_board_assigned:'.$userId);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @return array{todo: int, in_progress: int, total: int}
     */
    private static function computeAssignedCounts(string $userId): array
    {
        $rows = TaskTicket::query()
            ->select('task_columns.name', DB::raw('COUNT(*) as aggregate'))
            ->join('task_columns', 'task_columns.id', '=', 'task_tickets.column_id')
            ->join('task_ticket_assignees', 'task_ticket_assignees.ticket_id', '=', 'task_tickets.id')
            ->where('task_ticket_assignees.user_id', $userId)
            ->whereNull('task_tickets.deleted_at')
            ->groupBy('task_columns.name')
            ->pluck('aggregate', 'name');

        $todo = 0;
        $inProgress = 0;

        foreach ($rows as $name => $count) {
            $bucket = self::columnBucket((string) $name);
            $count = (int) $count;

            if ($bucket === 'todo') {
                $todo += $count;
            } elseif ($bucket === 'in_progress') {
                $inProgress += $count;
            }
        }

        return [
            'todo' => $todo,
            'in_progress' => $inProgress,
            'total' => $todo + $inProgress,
        ];
    }

    private static function columnBucket(string $name): ?string
    {
        $normalized = strtolower(trim($name));

        if (in_array($normalized, self::TODO_COLUMN_NAMES, true)) {
            return 'todo';
        }

        if (in_array($normalized, self::IN_PROGRESS_COLUMN_NAMES, true)) {
            return 'in_progress';
        }

        return null;
    }

    /**
     * @return array{todo: int, in_progress: int, total: int}
     */
    private static function emptyCounts(): array
    {
        return [
            'todo' => 0,
            'in_progress' => 0,
            'total' => 0,
        ];
    }
}
