@extends('adminmodule::layouts.new-master')

@section('title', 'Training setup')

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">Live</h2>
                    <div class="trn-meta mt-1">Who is in a lesson, who has not started, and who has finished.</div>
                </div>
                @include('adminmodule::admin.training.manage._nav')
            </div>

            @foreach([
                'In a lesson now' => $live,
                'Assigned, not started' => $idle,
                'Finished' => $finished,
            ] as $title => $rows)
                <div class="card">
                    <div class="card-body">
                        <div class="fw-semibold mb-1">{{ $title }} <span class="trn-meta">{{ $rows->count() }}</span></div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Program</th>
                                    <th>Where</th>
                                    <th>Result so far</th>
                                    <th>Restarts</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($rows as $assignment)
                                    @php $snap = $assignment->progress?->snapshot(); @endphp
                                    <tr>
                                        <td>{{ \Modules\AdminModule\Support\TrainingPeople::name($assignment->user) }}</td>
                                        <td>{{ $assignment->program->name }}</td>
                                        <td>@include('adminmodule::admin.training._badge', ['text' => $snap['message'] ?? 'Not opened yet', 'tone' => $snap['where_tone'] ?? 'muted'])</td>
                                        <td class="trn-meta">
                                            @php
                                                $lessonTotal = (int) $assignment->program->lessons_count;
                                                $lessonDone = $assignment->progress?->certified_at ? $lessonTotal : (int) $assignment->lessons_done_count;
                                            @endphp
                                            @if($lessonTotal > 0)
                                                {{ $lessonDone }} of {{ $lessonTotal }} lessons
                                            @else
                                                {{ $snap['quiz'] ?? 'Not submitted' }}
                                            @endif
                                        </td>
                                        <td class="trn-meta">
                                            @if((int) ($assignment->progress->restart_count ?? 0) > 0)
                                                <a href="{{ route('admin.training.attempts', $assignment) }}">{{ (int) $assignment->progress->restart_count }} {{ (int) $assignment->progress->restart_count === 1 ? 'time' : 'times' }}</a>
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">None.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
