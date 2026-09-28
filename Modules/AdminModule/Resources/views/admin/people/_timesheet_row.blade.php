@php
    $task = (string) ($row['task'] ?? '');
    $ticketId = (string) ($row['ticket_id'] ?? '');
    $hours = $row['hours'] ?? '';
    if ($hours === 0 || $hours === '0' || $hours === 0.0) {
        $hours = '';
    }
    $deadline = (string) ($row['deadline'] ?? '');
    $knownIds = collect($tasks)->pluck('id')->all();
    $matched = $ticketId !== '' && in_array($ticketId, $knownIds, true);
    if (! $matched) {
        $matched = collect($tasks)->contains(fn ($option) => $option['task'] === $task);
    }
@endphp
<tr class="ts-row">
    <td>
        <select class="ts-task-select" name="rows[{{ $index }}][ticket_id]" data-task @disabled($locked)>
            <option value="">Select task</option>
            @foreach($tasks as $option)
                <option
                    value="{{ $option['id'] }}"
                    data-title="{{ $option['task'] }}"
                    data-deadline="{{ $option['deadline'] }}"
                    @selected($ticketId === $option['id'] || ($ticketId === '' && $task === $option['task']))
                >{{ $option['task'] }}</option>
            @endforeach
            @if($task !== '' && ! $matched)
                <option value="{{ $ticketId !== '' ? $ticketId : $task }}" data-title="{{ $task }}" data-deadline="{{ $deadline }}" selected>{{ $task }}</option>
            @endif
        </select>
        <input type="hidden" class="ts-task-title" name="rows[{{ $index }}][task]" value="{{ $task }}">
    </td>
    <td><input class="ts-due" name="rows[{{ $index }}][deadline]" type="date" value="{{ $deadline }}" readonly tabindex="-1"></td>
    <td>
        <div class="ts-hours-wrap">
            <input class="ts-hours" name="rows[{{ $index }}][hours]" type="number" min="0" max="24" step="0.5" inputmode="decimal" value="{{ $hours }}" @disabled($locked)>
            @unless($locked)
                <button type="button" class="ts-remove" aria-label="Remove row">×</button>
            @endunless
        </div>
    </td>
</tr>
