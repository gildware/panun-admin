@php
    $task = (string) ($row['task'] ?? '');
    $ticketId = (string) ($row['ticket_id'] ?? '');
    if ($ticketId === 'leave') {
        $task = \Modules\AdminModule\Services\PeopleWorkspace::leaveTaskLabel($leaveName ?? '');
    }
    $hours = $row['hours'] ?? '';
    if ($hours === 0 || $hours === '0' || $hours === 0.0) {
        $hours = '';
    }
    $fromTime = (string) ($row['from_time'] ?? '');
    $toTime = (string) ($row['to_time'] ?? '');
    $manualHours = $ticketId === 'leave';
    if (! $manualHours && preg_match('/^(\d{2}):(\d{2})/', $fromTime, $fromMatch) && preg_match('/^(\d{2}):(\d{2})/', $toTime, $toMatch)) {
        $startMinutes = ((int) $fromMatch[1] * 60) + (int) $fromMatch[2];
        $endMinutes = ((int) $toMatch[1] * 60) + (int) $toMatch[2];
        if ($endMinutes > $startMinutes) {
            $hours = round(($endMinutes - $startMinutes) / 60, 2);
        }
    }
    $showTimes = ! $manualHours || ($fromTime !== '' && $toTime !== '');
    $deadline = (string) ($row['deadline'] ?? '');
    $description = (string) ($row['description'] ?? '');
    if ($ticketId === 'leave') {
        $reason = trim((string) ($leaveReason ?? ''));
        if ($reason !== '') {
            $description = $reason;
        }
    }
    $kind = ($kind ?? 'task') === 'extra' ? 'extra' : 'task';
    $options = $kind === 'extra' ? ($extras ?? []) : ($assigned ?? []);
    $knownIds = collect($options)->pluck('id')->all();
    $matched = $ticketId !== '' && in_array($ticketId, $knownIds, true);
    if (! $matched) {
        $matched = collect($options)->contains(fn ($option) => $option['task'] === $task);
    }
    $placeholder = $kind === 'extra' ? 'Select additional hours' : 'Select task';
    $hoursLocked = (bool) $locked && ! ($hoursOpen ?? false);
    $canRevokeLeave = (bool) ($canRevokeLeave ?? false);
@endphp
<div class="ts-row">
    <div class="ts-entry-top{{ $showTimes ? ' has-times' : '' }}{{ $locked ? '' : ' has-remove' }}{{ $canRevokeLeave ? ' has-revoke' : '' }}">
        <div class="ts-entry-main">
            <div class="ts-field ts-field-task">
                <span class="ts-field-label">{{ $kind === 'extra' ? 'Additional hours' : 'Task' }}</span>
                @if($locked && ($hoursOpen ?? false))
                    <input type="hidden" name="rows[{{ $index }}][ticket_id]" value="{{ $ticketId }}">
                @endif
                <select class="ts-task-select" name="rows[{{ $index }}][ticket_id]" data-task data-placeholder="{{ $placeholder }}" @disabled($locked)>
                    <option value="">{{ $placeholder }}</option>
                    @foreach($options as $option)
                        <option
                            value="{{ $option['id'] }}"
                            data-title="{{ $option['task'] }}"
                            data-deadline="{{ $option['deadline'] }}"
                            @selected($ticketId === $option['id'] || ($ticketId === '' && $task === $option['task']))
                        >{{ $option['task'] }}</option>
                    @endforeach
                    @if(in_array($ticketId, ['leave', 'partial-leave'], true))
                        <option value="{{ $ticketId }}" data-title="{{ $task !== '' ? $task : ($ticketId === 'leave' ? 'Leave' : 'Partial Leave') }}" data-deadline="{{ $deadline }}" selected>{{ $task !== '' ? $task : ($ticketId === 'leave' ? 'Leave' : 'Partial Leave') }}</option>
                    @elseif($locked && $task !== '' && ! $matched)
                        <option value="{{ $ticketId !== '' ? $ticketId : $task }}" data-title="{{ $task }}" data-deadline="{{ $deadline }}" selected>{{ $task }}</option>
                    @endif
                </select>
                <input type="hidden" class="ts-task-title" name="rows[{{ $index }}][task]" value="{{ $task }}">
                <input type="hidden" class="ts-due" name="rows[{{ $index }}][deadline]" value="{{ $deadline }}">
            </div>
            @unless($showTimes)
                <div class="ts-field ts-field-hours">
                    <span class="ts-field-label">Hours</span>
                    @if($manualHours)
                        <input class="ts-hours" name="rows[{{ $index }}][hours]" type="number" min="0" max="24" step="0.5" inputmode="decimal" placeholder="0" data-manual="1" value="{{ $hours }}" @disabled($hoursLocked)>
                    @else
                        <input class="ts-hours" type="hidden" name="rows[{{ $index }}][hours]" value="{{ $hours }}">
                        <input class="ts-hours-view" type="text" readonly tabindex="-1" placeholder="0h 00m" value="{{ $hours !== '' ? $fmt((float) $hours, true) : '' }}">
                    @endif
                </div>
            @endunless
            @if($canRevokeLeave)
                <button class="ts-leave-revoke" type="button" data-bs-toggle="modal" data-bs-target="#revokeLeaveModal" data-action="{{ $leaveRevokeAction ?? '' }}" data-summary="{{ $leaveRevokeSummary ?? '' }}">Revoke</button>
            @endif
            @unless($locked)
                <button type="button" class="ts-remove" aria-label="Remove row">×</button>
            @endunless
        </div>
        @if($showTimes)
            <div class="ts-entry-times">
                <div class="ts-field ts-field-time">
                    <span class="ts-field-label">From Time</span>
                    <input class="ts-from" name="rows[{{ $index }}][from_time]" type="hidden" value="{{ substr($fromTime, 0, 5) }}" @disabled($locked || $manualHours)>
                    <div class="people-time-control">
                        <input class="people-time-entry" type="text" inputmode="text" autocomplete="off" spellcheck="false" placeholder="--:--" aria-label="From time" @disabled($locked || $manualHours)>
                        <button type="button" class="people-time-btn" aria-label="Choose from time" @disabled($locked || $manualHours)>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v4.5l3 2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </button>
                    </div>
                </div>
                <div class="ts-field ts-field-time">
                    <span class="ts-field-label">To Time</span>
                    <input class="ts-to" name="rows[{{ $index }}][to_time]" type="hidden" value="{{ substr($toTime, 0, 5) }}" @disabled($locked || $manualHours)>
                    <div class="people-time-control">
                        <input class="people-time-entry" type="text" inputmode="text" autocomplete="off" spellcheck="false" placeholder="--:--" aria-label="To time" @disabled($locked || $manualHours)>
                        <button type="button" class="people-time-btn" aria-label="Choose to time" @disabled($locked || $manualHours)>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v4.5l3 2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </button>
                    </div>
                </div>
                <div class="ts-field ts-field-hours">
                    <span class="ts-field-label">Hours</span>
                    @if($manualHours)
                        <input class="ts-hours" name="rows[{{ $index }}][hours]" type="number" min="0" max="24" step="0.5" inputmode="decimal" placeholder="0" data-manual="1" value="{{ $hours }}" @disabled($hoursLocked)>
                    @else
                        <input class="ts-hours" type="hidden" name="rows[{{ $index }}][hours]" value="{{ $hours }}">
                        <input class="ts-hours-view" type="text" readonly tabindex="-1" placeholder="0h 00m" value="{{ $hours !== '' ? $fmt((float) $hours, true) : '' }}">
                    @endif
                </div>
            </div>
        @endif
    </div>
    <label class="ts-field ts-field-desc">
        <span class="ts-field-label">Description</span>
        <textarea class="ts-desc" name="rows[{{ $index }}][description]" rows="2" maxlength="500" placeholder="What did you work on?" @disabled($locked)>{{ $description }}</textarea>
    </label>
</div>
