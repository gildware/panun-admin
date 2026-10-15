@if($assignment->progress?->canRestart())
    <form class="d-inline" method="POST" action="{{ route('admin.training.restart', $assignment) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from('Restart '.$assignment->program->name.'? This attempt is saved in the training record, then the course starts again from the first lesson.') }})">
        @csrf
        <button class="btn btn-outline-secondary btn-sm" type="submit">Restart</button>
    </form>
    @if((int) $assignment->progress->restart_count > 0)
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.attempts', $assignment) }}">Earlier attempts ({{ (int) $assignment->progress->restart_count }})</a>
    @endif
@endif
