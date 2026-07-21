<?php

namespace Modules\AdminModule\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Modules\AdminModule\Entities\StaffActivityEvent;
use Modules\LeadManagement\Entities\Lead;

class StaffActivityLogger
{
    public function logLeadAssigned(
        string $employeeId,
        int|string $leadId,
        ?string $fromHandler,
        ?string $actorId = null,
        array $extraMeta = []
    ): void {
        if (! Lead::assigneeIsHuman($employeeId)) {
            return;
        }

        $from = $this->normalizeHandler($fromHandler);
        if ($from === (string) $employeeId) {
            return;
        }

        $this->record(
            employeeId: (string) $employeeId,
            eventType: StaffActivityEvent::TYPE_LEAD_ASSIGNED,
            subjectType: 'lead',
            subjectId: (string) $leadId,
            actorId: $actorId,
            meta: array_merge([
                'from_handler' => $from,
                'from_kind' => $this->handlerKind($from),
            ], $extraMeta)
        );
    }

    public function logWhatsAppAssigned(
        string $employeeId,
        string $phone,
        ?string $fromHandler,
        ?string $actorId = null,
        array $extraMeta = []
    ): void {
        if (! Lead::assigneeIsHuman($employeeId)) {
            return;
        }

        $from = $this->normalizeHandler($fromHandler);
        if ($from === (string) $employeeId) {
            return;
        }

        $fromKind = $this->handlerKind($from);
        $eventType = $fromKind === 'employee'
            ? StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_EMPLOYEE
            : StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_AI;

        $this->record(
            employeeId: (string) $employeeId,
            eventType: $eventType,
            subjectType: 'whatsapp_thread',
            subjectId: $phone,
            actorId: $actorId,
            meta: array_merge([
                'phone' => $phone,
                'from_handler' => $from,
                'from_kind' => $fromKind,
            ], $extraMeta)
        );
    }

    public function logWhatsAppChatClosed(
        string $employeeId,
        string $phone,
        ?int $statusId = null,
        ?string $actorId = null,
        array $extraMeta = []
    ): void {
        if (! Lead::assigneeIsHuman($employeeId)) {
            return;
        }

        $this->record(
            employeeId: (string) $employeeId,
            eventType: StaffActivityEvent::TYPE_WHATSAPP_CHAT_CLOSED,
            subjectType: 'whatsapp_thread',
            subjectId: $phone,
            actorId: $actorId,
            meta: array_merge([
                'phone' => $phone,
                'whatsapp_chat_status_id' => $statusId,
            ], $extraMeta)
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function record(
        string $employeeId,
        string $eventType,
        ?string $subjectType,
        ?string $subjectId,
        ?string $actorId,
        array $meta = []
    ): void {
        if (! Schema::hasTable('staff_activity_events')) {
            return;
        }

        StaffActivityEvent::query()->create([
            'employee_id' => $employeeId,
            'actor_id' => $actorId ?? (Auth::id() ? (string) Auth::id() : null),
            'event_type' => $eventType,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    private function normalizeHandler(?string $handler): ?string
    {
        $handler = $handler !== null ? trim($handler) : null;
        if ($handler === null || $handler === '') {
            return null;
        }

        return $handler;
    }

    private function handlerKind(?string $handler): string
    {
        if ($handler === null || $handler === '' || $handler === Lead::HANDLED_BY_AI) {
            return 'ai';
        }

        return Lead::assigneeIsHuman($handler) ? 'employee' : 'ai';
    }
}
