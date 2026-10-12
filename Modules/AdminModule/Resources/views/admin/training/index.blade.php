@extends('adminmodule::layouts.new-master')

@section('title', 'My training')

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">My training</h2>
                    <div class="trn-meta mt-1">Each course on one line, with progress, one result, and the deadline.</div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Program</th>
                                <th>Progress</th>
                                <th>Result</th>
                                <th>Deadline</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($assignments as $assignment)
                                @php
                                    if ($assignment->relationLoaded('progress') && $assignment->progress) {
                                        $assignment->progress->setRelation('assignment', $assignment);
                                    }
                                    $snap = $assignment->progress?->snapshot() ?? [
                                        'steps' => 0,
                                        'where' => 'Not started',
                                        'message' => 'Not opened yet',
                                        'where_tone' => 'muted',
                                    ];
                                    $lessonTotal = (int) $assignment->program->lessons_count;
                                    $lessonDone = $assignment->progress?->certified_at ? $lessonTotal : (int) $assignment->lessons_done_count;
                                    $lessonPct = $lessonTotal > 0 ? (int) round(($lessonDone / $lessonTotal) * 100) : ((int) $snap['steps'] * 25);
                                    $finished = ($snap['where'] ?? '') === 'Finished';
                                    $overdue = ! $finished && $assignment->due_at && $assignment->due_at->toDateString() < now()->toDateString();
                                    $result = $overdue ? 'Past the deadline' : ($snap['message'] ?? 'Not opened yet');
                                    $resultTone = $overdue ? 'bad' : ($snap['where_tone'] ?? 'muted');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="lms-one-line">
                                            <span class="fw-semibold">{{ $assignment->program->name }}</span>
                                            @include('adminmodule::admin.training._difficulty', ['difficulty' => $assignment->program->difficulty])
                                        </div>
                                    </td>
                                    <td>
                                        <div class="lms-progress-cell">
                                            <div class="lms-bar {{ $finished ? 'is-done' : '' }}"><span style="width: {{ $finished ? 100 : $lessonPct }}%"></span></div>
                                            <span class="trn-meta">
                                                @if($lessonTotal > 0)
                                                    {{ $lessonDone }} of {{ $lessonTotal }}
                                                @else
                                                    {{ $snap['steps'] }} of 4
                                                @endif
                                                @if((int) ($assignment->progress->restart_count ?? 0) > 0)
                                                    · <a href="{{ route('admin.training.attempts', $assignment) }}">Restarted {{ (int) $assignment->progress->restart_count }} {{ (int) $assignment->progress->restart_count === 1 ? 'time' : 'times' }}</a>
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td>@include('adminmodule::admin.training._badge', ['text' => $result, 'tone' => $resultTone])</td>
                                    <td class="trn-meta">{{ $assignment->due_at ? $assignment->due_at->format('j M Y') : '—' }}</td>
                                    <td class="text-end">
                                        @php
                                            $openRoute = 'admin.training.content';
                                            if ($assignment->progress) {
                                                if (! $assignment->progress->content_done_at) {
                                                    $openRoute = 'admin.training.content';
                                                } elseif ($snap['quiz_blocking']) {
                                                    $openRoute = 'admin.training.questions';
                                                } elseif ($snap['ai_blocking']) {
                                                    $openRoute = 'admin.training.ai';
                                                } else {
                                                    $openRoute = 'admin.training.certificate';
                                                }
                                            }
                                        @endphp
                                        <div class="d-flex justify-content-end gap-2">
                                            @include('adminmodule::admin.training._restart', ['assignment' => $assignment])
                                            <a class="btn btn--primary btn-sm" href="{{ route($openRoute, $assignment) }}">Open</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">No programs are assigned to you yet.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
