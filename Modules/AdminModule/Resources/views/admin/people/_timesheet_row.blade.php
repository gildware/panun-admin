@php
    $task = (string) ($row['task'] ?? '');
    $code = (string) ($row['code'] ?? '');
    $type = (string) ($row['project_type'] ?? '');
    $hours = $row['hours'] ?? '';
    if ($hours === 0 || $hours === '0' || $hours === 0.0) {
        $hours = '';
    }
    $deadline = (string) ($row['deadline'] ?? '');
@endphp
<tr class="ts-row">
    <td>
        <div class="ts-task">
            <select class="ts-task-select" name="rows[{{ $index }}][task]" data-task @disabled($locked)>
                <option value="">Select task</option>
                @foreach($tasks as $option)
                    <option value="{{ $option['task'] }}" data-code="{{ $option['code'] }}" data-type="{{ $option['project_type'] }}" @selected($task === $option['task'])>{{ $option['task'] }}</option>
                @endforeach
            </select>
            <button type="button" class="ts-copy" aria-label="Copy project code"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M6 16V6a1 1 0 0 1 1-1h10" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></button>
        </div>
    </td>
    <td><input class="ts-code" name="rows[{{ $index }}][code]" value="{{ $code }}" readonly tabindex="-1"></td>
    <td><input type="date" name="rows[{{ $index }}][deadline]" value="{{ $deadline }}" @disabled($locked)></td>
    <td><input class="ts-type" name="rows[{{ $index }}][project_type]" value="{{ $type }}" readonly tabindex="-1"></td>
    <td>
        <div class="ts-hours-wrap">
            <input class="ts-hours" name="rows[{{ $index }}][hours]" type="number" min="0" max="24" step="0.5" inputmode="decimal" value="{{ $hours }}" @disabled($locked)>
            @unless($locked)
                <button type="button" class="ts-remove" aria-label="Remove row">×</button>
            @endunless
        </div>
    </td>
</tr>
