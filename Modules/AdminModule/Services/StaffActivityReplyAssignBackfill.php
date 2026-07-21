<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AdminModule\Entities\StaffActivityEvent;
use Modules\LeadManagement\Entities\Lead;
use Modules\UserManagement\Entities\User;

/**
 * Reconstruct WA "assigned from AI" events for employees who self-assigned
 * by replying before persistChatHandler started logging.
 */
class StaffActivityReplyAssignBackfill
{
    /**
     * @return array{inserted: int, skipped: int, candidates: int}
     */
    public function run(?Carbon $since = null, ?Carbon $until = null, bool $dryRun = false): array
    {
        if (! Schema::hasTable('staff_activity_events')) {
            return ['inserted' => 0, 'skipped' => 0, 'candidates' => 0];
        }

        $messageTable = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        if (! Schema::hasTable($messageTable) || ! Schema::hasColumn($messageTable, 'sent_by_id')) {
            return ['inserted' => 0, 'skipped' => 0, 'candidates' => 0];
        }

        $employeeIds = User::query()
            ->whereIn('user_type', ['super-admin', 'admin-employee'])
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($employeeIds === []) {
            return ['inserted' => 0, 'skipped' => 0, 'candidates' => 0];
        }

        $query = DB::table($messageTable)
            ->selectRaw('phone, sent_by_id, MIN(created_at) as first_at')
            ->where('direction', 'OUT')
            ->whereNotNull('sent_by_id')
            ->whereIn('sent_by_id', $employeeIds)
            ->groupBy('phone', 'sent_by_id');

        if ($since) {
            $query->havingRaw('MIN(created_at) >= ?', [$since->toDateTimeString()]);
        }
        if ($until) {
            $query->havingRaw('MIN(created_at) <= ?', [$until->toDateTimeString()]);
        }

        $candidates = $query->get();
        $inserted = 0;
        $skipped = 0;

        foreach ($candidates as $row) {
            $phone = (string) $row->phone;
            $employeeId = (string) $row->sent_by_id;
            $firstAt = Carbon::parse($row->first_at);

            if ($phone === '' || ! Lead::assigneeIsHuman($employeeId)) {
                $skipped++;
                continue;
            }

            $exists = StaffActivityEvent::query()
                ->where('employee_id', $employeeId)
                ->where('subject_type', 'whatsapp_thread')
                ->where('subject_id', $phone)
                ->whereIn('event_type', [
                    StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_AI,
                    StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_EMPLOYEE,
                ])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $inserted++;
                continue;
            }

            $event = new StaffActivityEvent([
                'employee_id' => $employeeId,
                'actor_id' => $employeeId,
                'event_type' => StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_AI,
                'subject_type' => 'whatsapp_thread',
                'subject_id' => $phone,
                'meta' => [
                    'phone' => $phone,
                    'from_handler' => null,
                    'from_kind' => 'ai',
                    'source' => 'backfill_first_reply',
                ],
            ]);
            $event->created_at = $firstAt;
            $event->updated_at = $firstAt;
            $event->save();

            $inserted++;
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'candidates' => $candidates->count(),
        ];
    }
}
