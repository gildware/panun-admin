<?php

namespace Modules\CallCenterModule\Services;

use Modules\BookingModule\Entities\Booking;
use Modules\CallCenterModule\Entities\AiAnalysis;
use Modules\CallCenterModule\Entities\Call;
use Modules\CallCenterModule\Entities\CustomerProfile;
use Modules\CallCenterModule\Entities\Note;
use Modules\CallCenterModule\Entities\Task;
use Modules\CallCenterModule\Entities\Voicemail;
use Modules\CallCenterModule\Transformers\BookingTransformer;
use Modules\ProviderManagement\Entities\CustomerIncident;

class CustomerTimelineService
{
    public function __construct(private readonly BookingTransformer $bookingTransformer)
    {
    }

    public function build(CustomerProfile $profile, int $page, int $perPage): array
    {
        $events = collect();

        Call::query()
            ->where('customer_profile_id', $profile->id)
            ->get()
            ->each(function (Call $call) use ($events) {
                $duration = $call->duration_seconds
                    ? sprintf('%dm %ds', intdiv($call->duration_seconds, 60), $call->duration_seconds % 60)
                    : null;
                $summaryParts = array_filter([
                    $duration ? "Duration {$duration}" : null,
                    $call->disposition,
                ]);

                $events->push([
                    'type' => 'call',
                    'id' => $call->id,
                    'occurred_at' => ($call->started_at ?? $call->created_at)?->utc()->toIso8601String(),
                    'title' => ucfirst($call->direction) . ' call',
                    'summary' => $summaryParts ? implode(' — ', $summaryParts) : ucfirst($call->status),
                    'metadata' => [
                        'direction' => $call->direction,
                        'agent_name' => $call->agent_name,
                        'status' => $call->status,
                    ],
                    '_sort' => ($call->started_at ?? $call->created_at)?->timestamp ?? 0,
                ]);
            });

        Note::query()
            ->where('customer_profile_id', $profile->id)
            ->get()
            ->each(function (Note $note) use ($events) {
                $events->push([
                    'type' => 'note',
                    'id' => $note->id,
                    'occurred_at' => ($note->noted_at ?? $note->created_at)?->utc()->toIso8601String(),
                    'title' => ucfirst(str_replace('_', ' ', $note->note_type)),
                    'summary' => str($note->content)->limit(120)->toString(),
                    'metadata' => [
                        'agent_name' => $note->agent_name,
                        'note_type' => $note->note_type,
                    ],
                    '_sort' => ($note->noted_at ?? $note->created_at)?->timestamp ?? 0,
                ]);
            });

        Voicemail::query()
            ->where('customer_profile_id', $profile->id)
            ->get()
            ->each(function (Voicemail $vm) use ($events) {
                $events->push([
                    'type' => 'voicemail',
                    'id' => $vm->id,
                    'occurred_at' => $vm->received_at?->utc()->toIso8601String(),
                    'title' => 'Voicemail',
                    'summary' => $vm->duration_seconds ? "{$vm->duration_seconds}s from {$vm->from_number}" : "From {$vm->from_number}",
                    'metadata' => ['status' => $vm->status],
                    '_sort' => $vm->received_at?->timestamp ?? 0,
                ]);
            });

        Task::query()
            ->where('customer_profile_id', $profile->id)
            ->get()
            ->each(function (Task $task) use ($events) {
                $occurredAt = $task->completed_at ?? $task->created_at;
                $events->push([
                    'type' => 'task',
                    'id' => $task->id,
                    'occurred_at' => $occurredAt?->utc()->toIso8601String(),
                    'title' => $task->title,
                    'summary' => $task->description ? str($task->description)->limit(120)->toString() : ucfirst($task->status),
                    'metadata' => [
                        'priority' => $task->priority,
                        'status' => $task->status,
                    ],
                    '_sort' => $occurredAt?->timestamp ?? 0,
                ]);
            });

        AiAnalysis::query()
            ->whereIn('call_id', Call::query()->where('customer_profile_id', $profile->id)->select('id'))
            ->get()
            ->each(function (AiAnalysis $analysis) use ($events) {
                $events->push([
                    'type' => 'ai_summary',
                    'id' => $analysis->id,
                    'occurred_at' => ($analysis->processed_at ?? $analysis->created_at)?->utc()->toIso8601String(),
                    'title' => 'AI analysis',
                    'summary' => $analysis->summary ?? str($analysis->transcript)->limit(120)->toString(),
                    'metadata' => [
                        'intent' => $analysis->intent,
                        'sentiment' => $analysis->sentiment,
                    ],
                    '_sort' => ($analysis->processed_at ?? $analysis->created_at)?->timestamp ?? 0,
                ]);
            });

        Booking::query()
            ->where('customer_id', $profile->user_id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->each(function (Booking $booking) use ($events) {
                $transformed = $this->bookingTransformer->transform($booking);
                $events->push([
                    'type' => 'booking',
                    'id' => $booking->id,
                    'occurred_at' => ($booking->service_schedule ?? $booking->created_at)?->utc()->toIso8601String(),
                    'title' => 'Booking ' . ($booking->readable_id ?? $booking->id),
                    'summary' => ucfirst($transformed['status']) . ' — ' . $transformed['service_type'],
                    'metadata' => ['booking_ref' => $booking->readable_id],
                    '_sort' => ($booking->service_schedule ?? $booking->created_at)?->timestamp ?? 0,
                ]);
            });

        CustomerIncident::query()
            ->where('customer_id', $profile->user_id)
            ->where('incident_type', 'COMPLAINT')
            ->get()
            ->each(function (CustomerIncident $incident) use ($events) {
                $events->push([
                    'type' => 'complaint',
                    'id' => $incident->id,
                    'occurred_at' => $incident->created_at?->utc()->toIso8601String(),
                    'title' => 'Complaint',
                    'summary' => str($incident->notes)->limit(120)->toString(),
                    'metadata' => ['incident_type' => $incident->incident_type],
                    '_sort' => $incident->created_at?->timestamp ?? 0,
                ]);
            });

        $sorted = $events->sortByDesc('_sort')->values();
        $total = $sorted->count();
        $offset = ($page - 1) * $perPage;
        $pageItems = $sorted->slice($offset, $perPage)->map(function (array $item) {
            unset($item['_sort']);

            return $item;
        })->values()->all();

        return [
            'data' => $pageItems,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => max(1, (int) ceil($total / max(1, $perPage))),
            ],
        ];
    }
}
