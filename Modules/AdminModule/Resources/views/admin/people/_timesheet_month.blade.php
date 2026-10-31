@php
    $taskTotal = 0.0;
    $additionalTotal = 0.0;
    $overtimeTotal = 0.0;
    $leaveTotal = 0.0;
    foreach ($timesheetReview['days'] as $sumDay) {
        $taskTotal += (float) $sumDay['task'];
        $additionalTotal += (float) $sumDay['additional'];
        $overtimeTotal += (float) ($sumDay['overtime'] ?? 0);
        $leaveTotal += (float) $sumDay['leave'];
    }
    $hoursText = $hoursText ?? fn (float $hours) => \Modules\AdminModule\Services\PeopleWorkspace::hoursText($hours);
@endphp
<article class="people-ws-card people-ws-scroll people-ts-month">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Work detail</th>
                <th>Overtime</th>
                <th class="num">Task hours</th>
                <th class="num">Additional hrs</th>
                <th class="num">Overtime hrs</th>
                <th class="num">Leave hours</th>
                <th class="num">Total hours</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($timesheetReview['days'] as $day)
            @php
                $workChips = $day['chips'] ?? [];
                $overtimeChips = $day['overtimeChips'] ?? [];
            @endphp
            <tr @class(['is-even' => ((int) substr($day['date'], 8, 2)) % 2 === 0])>
                <td>{{ $day['label'] }}</td>
                <td>
                    @if($workChips === [])
                        —
                    @else
                        <div class="people-ts-chips">
                            @foreach($workChips as $index => $chip)
                                <span class="people-ts-chip" title="{{ $chip }}" @if($index >= 4) hidden data-extra-chip @endif>{{ $chip }}</span>
                            @endforeach
                            @if(count($workChips) > 4)
                                <button type="button" class="people-ts-chip is-more" data-show-chips title="{{ implode(', ', array_slice($workChips, 4)) }}">...</button>
                            @endif
                        </div>
                    @endif
                </td>
                <td>
                    @if($overtimeChips === [])
                        —
                    @else
                        <div class="people-ts-chips">
                            @foreach($overtimeChips as $index => $chip)
                                <span class="people-ts-chip is-overtime" title="{{ $chip }}" @if($index >= 4) hidden data-extra-chip @endif>{{ $chip }}</span>
                            @endforeach
                            @if(count($overtimeChips) > 4)
                                <button type="button" class="people-ts-chip is-more is-overtime" data-show-chips title="{{ implode(', ', array_slice($overtimeChips, 4)) }}">...</button>
                            @endif
                        </div>
                    @endif
                </td>
                <td class="num">{{ $hoursText((float) $day['task']) }}</td>
                <td class="num">{{ $hoursText((float) $day['additional']) }}</td>
                <td class="num">{{ $hoursText((float) ($day['overtime'] ?? 0)) }}</td>
                <td class="num">{{ $hoursText((float) $day['leave']) }}</td>
                <td class="num">{{ $hoursText((float) $day['total']) }}</td>
                <td class="actions">
                    <div class="people-ws-actions">
                        @if(($day['rows'] ?? []) !== [])
                            <button
                                class="btn-pw people-ts-view-btn"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#viewTimesheetDayModal"
                                data-title="{{ $timesheetReview['name'] }} · {{ $day['label'] }}"
                                data-rows="{{ json_encode($day['rows'], JSON_UNESCAPED_UNICODE) }}"
                                title="View timesheet details"
                                aria-label="View timesheet details"
                            ><span class="material-icons" aria-hidden="true">visibility</span></button>
                        @endif
                        @if($day['sheet'] && ! empty($day['actionable']))
                            <button
                                class="btn-pw good"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#approveTimesheetModal"
                                data-action="{{ route('admin.people.team.timesheet.decide', $day['sheet']) }}"
                                data-employee="{{ $employeeId }}"
                                data-timesheet="{{ $day['sheet']->id }}"
                                data-date="{{ $day['date'] }}"
                                data-summary="Approve {{ $timesheetReview['name'] }}’s timesheet for {{ $day['label'] }}?"
                            >Approve</button>
                            <button
                                class="btn-pw danger"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectTimesheetModal"
                                data-action="{{ route('admin.people.team.timesheet.decide', $day['sheet']) }}"
                                data-employee="{{ $employeeId }}"
                                data-timesheet="{{ $day['sheet']->id }}"
                                data-date="{{ $day['date'] }}"
                                data-summary="Reject {{ $timesheetReview['name'] }}’s timesheet for {{ $day['label'] }}? They will need to fill this day again."
                            >Reject</button>
                        @else
                            @include('adminmodule::admin.people._badge', ['status' => $day['state']])
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="people-ws-note">No days in this month.</td></tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td></td>
                <td></td>
                <td class="num">{{ $hoursText($taskTotal) }}</td>
                <td class="num">{{ $hoursText($additionalTotal) }}</td>
                <td class="num">{{ $hoursText($overtimeTotal) }}</td>
                <td class="num">{{ $hoursText($leaveTotal) }}</td>
                <td class="num">{{ $hoursText($taskTotal + $additionalTotal + $overtimeTotal + $leaveTotal) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</article>
