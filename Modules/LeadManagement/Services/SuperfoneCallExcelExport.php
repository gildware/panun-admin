<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\LeadManagement\Entities\SuperfoneCall;
use Modules\LeadManagement\Entities\SuperfoneCallContact;

class SuperfoneCallExcelExport
{
    public const PREVIEW_LIMIT = 20;

    /**
     * @return array<string, array<string, string>>
     */
    public function groups(): array
    {
        return [
            translate('Call') => [
                'started_at' => translate('Time'),
                'call_id' => translate('Call_id'),
                'direction' => translate('Direction'),
                'phone' => translate('Phone'),
                'employee' => translate('Employee'),
                'result' => translate('Result'),
                'follow_up' => translate('Follow_up'),
                'duration' => translate('Duration'),
                'recording' => translate('Recording'),
                'disposition' => translate('Disposition'),
                'ringing_seconds' => translate('Ringing'),
                'answered_at' => translate('Answered_at'),
                'ended_at' => translate('Ended_at'),
            ],
            translate('Party') => [
                'party_type' => translate('Party_type'),
                'party_name' => translate('Customer_or_Provider'),
            ],
            translate('Lead') => [
                'lead_id' => translate('Lead_ID'),
                'lead_name' => translate('Lead_name'),
                'lead_type' => translate('Lead_Type'),
                'lead_phone' => translate('Lead_phone'),
            ],
            translate('Booking') => [
                'booking_id' => translate('Booking_ID'),
                'booking_status' => translate('Booking_status'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function flatColumns(): array
    {
        $columns = [];
        foreach ($this->groups() as $group) {
            foreach ($group as $key => $label) {
                $columns[$key] = $label;
            }
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    public function allKeys(): array
    {
        return array_keys($this->flatColumns());
    }

    /**
     * @return list<string>
     */
    public function defaultKeys(): array
    {
        return [
            'started_at',
            'call_id',
            'direction',
            'phone',
            'employee',
            'result',
            'follow_up',
            'duration',
            'recording',
            'party_type',
            'party_name',
            'lead_id',
            'lead_name',
            'lead_type',
            'booking_id',
            'booking_status',
        ];
    }

    /**
     * @return list<string>
     */
    public function resolveKeys(Request $request): array
    {
        $known = $this->allKeys();
        if (! $request->boolean('column_selection')) {
            return array_values(array_intersect($this->defaultKeys(), $known));
        }

        $requested = array_values(array_filter(array_map('strval', (array) $request->input('columns', []))));

        return array_values(array_intersect($known, $requested));
    }

    /**
     * @return array<string, string>
     */
    public function relations(): array
    {
        return [
            'lead:id,name,phone_number,lead_type',
            'employee:id,first_name,last_name',
            'provider:id,company_name,contact_person_name',
            'booking:id,readable_id,booking_status',
            'customer:id,first_name,last_name,phone',
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function resolveSort(Request $request): array
    {
        $sortable = $this->sortableColumns();
        $requestedSort = (string) $request->input('sort');
        $sort = (array_key_exists($requestedSort, $sortable) || in_array($requestedSort, ['party', 'booking'], true))
            ? $requestedSort
            : 'started_at';
        $direction = $request->input('dir', $request->input('direction')) === 'asc' ? 'asc' : 'desc';

        return [$sort, $direction];
    }

    /**
     * @return array<string, string>
     */
    public function sortOptions(): array
    {
        return [
            'started_at' => translate('Time'),
            'direction' => translate('Direction'),
            'phone' => translate('Phone'),
            'employee' => translate('Employee'),
            'outcome' => translate('Result'),
            'duration' => translate('Duration'),
            'recording' => translate('Recording'),
            'party' => translate('Customer_or_Provider'),
            'lead' => translate('Lead'),
            'booking' => translate('Booking'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function outcomeOptions(): array
    {
        return [
            'answered' => translate('Answered'),
            'missed' => translate('Missed'),
            'needs_callback' => translate('Needs_callback'),
            'contacted' => translate('Contacted'),
            'unanswered' => translate('Unanswered'),
            'other' => translate('Other'),
            'unknown' => translate('Unknown'),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    public function agents(): Collection
    {
        return SuperfoneCall::query()
            ->whereNotNull('staff_name')
            ->where('staff_name', '!=', '')
            ->distinct()
            ->orderBy('staff_name')
            ->pluck('staff_name');
    }

    public function query(Request $request): Builder
    {
        [$sort, $direction] = $this->resolveSort($request);
        $query = SuperfoneCall::query()->with($this->relations());
        $this->applyFilters($query, $request);
        $this->applySort($query, $sort, $direction);

        return $query;
    }

    public function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $digits = SuperfoneCallService::lastTenDigits($term);
            $query->where(function ($inner) use ($term, $digits) {
                $inner->where('customer_phone', 'like', '%'.$term.'%')
                    ->orWhere('staff_name', 'like', '%'.$term.'%')
                    ->orWhere('cdr_uuid', 'like', '%'.$term.'%');
                if ($digits !== '') {
                    $inner->orWhere('customer_phone_digits', 'like', '%'.$digits.'%');
                }
                $inner->orWhereHas('lead', function ($lead) use ($term) {
                    $lead->where('name', 'like', '%'.$term.'%')
                        ->orWhere('phone_number', 'like', '%'.$term.'%');
                });
            });
        }

        if (in_array($request->input('direction'), ['INBOUND', 'OUTBOUND'], true)) {
            $query->where('direction', $request->input('direction'));
        }

        if (in_array($request->input('outcome'), ['answered', 'missed', 'unanswered', 'needs_callback', 'contacted', 'other', 'unknown'], true)) {
            if ($request->input('outcome') === 'unknown') {
                $query->whereNull('outcome');
            } elseif ($request->input('outcome') === 'needs_callback') {
                $query->awaitingCallback();
            } elseif ($request->input('outcome') === 'contacted') {
                $query->contacted();
            } elseif ($request->input('outcome') === 'unanswered') {
                $query->where(function ($inner) {
                    $inner->where('outcome', 'unanswered')
                        ->orWhere(function ($legacy) {
                            $legacy->where('outcome', 'missed')->where('direction', 'OUTBOUND');
                        });
                });
            } elseif ($request->input('outcome') === 'missed') {
                $query->missed();
            } else {
                $query->where('outcome', $request->input('outcome'));
            }
        }

        if ($request->input('matched') === 'yes') {
            $query->where(function ($inner) {
                $inner->whereNotNull('lead_id')->orWhereNotNull('booking_id');
            });
        } elseif ($request->input('matched') === 'no') {
            $query->whereNull('lead_id')->whereNull('booking_id');
        }

        if ($request->filled('agent')) {
            $query->where('staff_name', $request->input('agent'));
        }

        if ($request->filled('from')) {
            $query->whereDate('started_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('started_at', '<=', $request->input('to'));
        }

        return $query;
    }

    public function applySort(Builder $query, string $sort, string $direction): void
    {
        $sortable = $this->sortableColumns();

        if ($sort === 'party') {
            $query->leftJoin('providers as call_providers', 'call_providers.id', '=', 'superfone_calls.provider_id')
                ->leftJoin('users as call_customers', 'call_customers.id', '=', 'superfone_calls.customer_user_id')
                ->select('superfone_calls.*')
                ->orderByRaw("COALESCE(NULLIF(call_providers.company_name, ''), NULLIF(call_providers.contact_person_name, ''), NULLIF(TRIM(CONCAT(COALESCE(call_customers.first_name, ''), ' ', COALESCE(call_customers.last_name, ''))), '')) {$direction}");
        } elseif ($sort === 'booking') {
            $query->leftJoin('bookings as call_bookings', 'call_bookings.id', '=', 'superfone_calls.booking_id')
                ->select('superfone_calls.*')
                ->orderByRaw('call_bookings.readable_id IS NULL')
                ->orderBy('call_bookings.readable_id', $direction);
        } else {
            $column = $sortable[$sort] ?? 'superfone_calls.started_at';
            $query->orderByRaw($column.' IS NULL')->orderBy($column, $direction);
        }
        $query->orderByDesc('superfone_calls.id');
    }

    /**
     * @return array<string, mixed>
     */
    public function sharedQuery(Request $request): array
    {
        [$sort, $direction] = $this->resolveSort($request);
        $shared = [
            'sort' => $sort,
            'dir' => $direction,
        ];

        foreach (['q', 'outcome', 'agent', 'matched', 'direction', 'from', 'to'] as $key) {
            $value = $request->input($key);
            if ($value !== null && $value !== '') {
                $shared[$key] = $value;
            }
        }

        return $shared;
    }

    /**
     * @param  list<string>  $keys
     * @return list<array<string, string>>
     */
    public function valueRows(Collection $calls, array $keys): array
    {
        if ($calls->isEmpty() || $keys === []) {
            return [];
        }

        $context = $this->buildContext($calls);
        $rows = [];
        foreach ($calls as $call) {
            $row = [];
            foreach ($keys as $key) {
                $row[$key] = $this->cell($call, $key, $context);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  list<string>  $keys
     * @return list<array<string, string>>
     */
    public function labeledRows(Collection $calls, array $keys): array
    {
        $labels = $this->flatColumns();
        $rows = [];
        foreach ($this->valueRows($calls, $keys) as $row) {
            $labeled = [];
            foreach ($keys as $key) {
                $labeled[$labels[$key] ?? $key] = $row[$key] ?? '';
            }
            $rows[] = $labeled;
        }

        return $rows;
    }

    /**
     * @return array{contactMarks: array<int, SuperfoneCallContact>, awaitingCallbackIds: array<int, int>}
     */
    private function buildContext(Collection $calls): array
    {
        return [
            'contactMarks' => SuperfoneCallContact::marksForCalls($calls),
            'awaitingCallbackIds' => SuperfoneCall::query()
                ->awaitingCallback()
                ->whereIn('superfone_calls.id', $calls->pluck('id'))
                ->pluck('superfone_calls.id')
                ->flip()
                ->all(),
        ];
    }

    /**
     * @param  array{contactMarks: array<int, SuperfoneCallContact>, awaitingCallbackIds: array<int, int>}  $context
     */
    private function cell(SuperfoneCall $call, string $key, array $context): string
    {
        $contactMark = $context['contactMarks'][$call->id] ?? null;
        $needsCallback = isset($context['awaitingCallbackIds'][$call->id]) && $contactMark === null;

        return match ($key) {
            'started_at' => $call->formatIst('started_at'),
            'call_id' => (string) ($call->cdr_uuid ?: ''),
            'direction' => $call->directionLabel(),
            'phone' => (string) ($call->customer_phone ?: ''),
            'employee' => (string) ($call->staff_name ?: ''),
            'result' => $call->outcomeLabel(),
            'follow_up' => $needsCallback
                ? translate('Needs_callback')
                : ($contactMark ? $contactMark->channelLabel() : ''),
            'duration' => $call->durationLabel(),
            'recording' => $call->hasRecording() ? translate('Yes') : translate('No'),
            'disposition' => (string) ($call->disposition ?: ''),
            'ringing_seconds' => $call->ringing_seconds !== null ? (string) $call->ringing_seconds : '',
            'answered_at' => $call->formatIst('answered_at'),
            'ended_at' => $call->formatIst('ended_at'),
            'party_type' => $call->provider
                ? translate('Provider')
                : ($call->customer ? translate('Customer') : ''),
            'party_name' => $this->partyName($call),
            'lead_id' => $call->lead_id ? (string) $call->lead_id : '',
            'lead_name' => (string) ($call->lead?->name ?: ''),
            'lead_type' => (string) ($call->lead?->lead_type ?: ''),
            'lead_phone' => (string) ($call->lead?->phone_number ?: ''),
            'booking_id' => (string) ($call->booking?->readable_id ?: ''),
            'booking_status' => $call->booking?->booking_status
                ? ucfirst(str_replace('_', ' ', (string) $call->booking->booking_status))
                : '',
            default => '',
        };
    }

    private function partyName(SuperfoneCall $call): string
    {
        if ($call->provider) {
            return trim((string) ($call->provider->company_name ?: $call->provider->contact_person_name));
        }
        if ($call->customer) {
            return trim((string) (($call->customer->first_name ?? '').' '.($call->customer->last_name ?? '')));
        }

        return '';
    }

    /**
     * @return array<string, string>
     */
    private function sortableColumns(): array
    {
        return [
            'started_at' => 'superfone_calls.started_at',
            'direction' => 'superfone_calls.direction',
            'phone' => 'superfone_calls.customer_phone',
            'employee' => 'superfone_calls.staff_name',
            'outcome' => 'superfone_calls.outcome',
            'duration' => 'superfone_calls.duration_seconds',
            'recording' => 'superfone_calls.recording_path',
            'lead' => 'superfone_calls.lead_id',
        ];
    }
}
