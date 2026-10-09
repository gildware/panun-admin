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
@endphp
<tr class="ts-row">
    <td>
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
    </td>
    <td>
        <input class="ts-hours" name="rows[{{ $index }}][hours]" type="number" min="0" max="24" step="0.5" inputmode="decimal" placeholder="Hours" value="{{ $hours }}" @disabled($hoursLocked)>
    </td>
    <td>
        <div class="ts-desc-wrap">
            <input class="ts-desc" name="rows[{{ $index }}][description]" type="text" maxlength="500" placeholder="Description" value="{{ $description }}" @disabled($locked)>
            @unless($locked)
                <button type="button" class="ts-remove" aria-label="Remove row">×</button>
            @endunless
        </div>
    </td>
</tr>
