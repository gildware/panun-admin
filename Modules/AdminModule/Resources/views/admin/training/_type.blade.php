@php
    $key = $type ?? 'text';
    $icons = [
        'text' => 'article',
        'audio' => 'headphones',
        'video' => 'play_circle',
        'quiz' => 'quiz',
        'ai' => 'psychology',
    ];
@endphp
<span class="lms-type lms-type--{{ $key }}" title="{{ \Modules\AdminModule\Entities\TrainingLesson::TYPES[$key] ?? 'Text' }}">
    <span class="material-icons">{{ $icons[$key] ?? 'article' }}</span>
</span>
