@php
    $key = $difficulty ?? 'standard';
    $label = \Modules\AdminModule\Entities\TrainingProgram::DIFFICULTIES[$key] ?? 'Standard';
    $style = match ($key) {
        'foundation' => 'background:#ecfdf3;color:#166534;',
        'advanced' => 'background:#fff7ed;color:#c2410c;',
        default => 'background:#eff6ff;color:#1d4ed8;',
    };
@endphp
<span class="badge" style="{{ $style }}">{{ $label }}</span>
