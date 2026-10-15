@php
    $taskTotal = 0.0;
    $additionalTotal = 0.0;
    $leaveTotal = 0.0;
    foreach ($timesheetReview['days'] as $sumDay) {
        $taskTotal += (float) $sumDay['task'];
        $additionalTotal += (float) $sumDay['additional'];
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
                <th class="num">Task hours</th>
                <th class="num">Additional hrs</th>
                <th class="num">Leave hours</th>
                <th class="num">Total hours</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($timesheetReview['days'] as $day)
            <tr @class(['is-even' => ((int) substr($day['date'], 8, 2)) % 2 === 0])>
                <td>{{ $day['label'] }}</td>
                <td>
                    @if($day['chips'] === [])
                        —
                    @else
                        <div class="people-ts-chips">
                            @foreach($day['chips'] as $index => $chip)
                                <span class="people-ts-chip" title="{{ $chip }}" @if($index >= 4) hidden data-extra-chip @endif>{{ $chip }}</span>
                            @endforeach
                            @if(count($day['chips']) > 4)
                                <button type="button" class="people-ts-chip is-more" data-show-chips title="{{ implode(', ', array_slice($day['chips'], 4)) }}">...</button>
                            @endif
                        </div>
                    @endif
                </td>
                <td class="num">{{ $hoursText((float) $day['task']) }}</td>
                <td class="num">{{ $hoursText((float) $day['additional']) }}</td>
                <td class="num">{{ $hoursText((float) $day['leave']) }}</td>
                <td class="num">{{ $hoursText((float) $day['total']) }}</td>
                <td class="actions">
                    @if($day['sheet'] && in_array($day['state'], ['pending', 'submitted', 'sent_back'], true))
                        @php
                            $weekLabel = $day['sheet']->week_starts_on->format('j M Y');
                            $decideSummary = $timesheetReview['name'].'’s timesheet for the week of '.$weekLabel;
                        @endphp
                        <div class="people-ws-actions">
                            <button
                                class="btn-pw good"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#approveTimesheetModal"
                                data-action="{{ route('admin.people.team.timesheet.decide', $day['sheet']) }}"
                                data-employee="{{ $employeeId }}"
                                data-timesheet="{{ $day['sheet']->id }}"
                                data-summary="Approve {{ $decideSummary }}?"
                            >Approve</button>
                            <button
                                class="btn-pw danger"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectTimesheetModal"
                                data-action="{{ route('admin.people.team.timesheet.decide', $day['sheet']) }}"
                                data-employee="{{ $employeeId }}"
                                data-timesheet="{{ $day['sheet']->id }}"
                                data-summary="Reject {{ $decideSummary }}?"
                            >Reject</button>
                        </div>
                    @else
                        @include('adminmodule::admin.people._badge', ['status' => $day['state']])
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="people-ws-note">No days in this month.</td></tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td></td>
                <td class="num">{{ $hoursText($taskTotal) }}</td>
                <td class="num">{{ $hoursText($additionalTotal) }}</td>
                <td class="num">{{ $hoursText($leaveTotal) }}</td>
                <td class="num">{{ $hoursText($taskTotal + $additionalTotal + $leaveTotal) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</article>
