@extends('adminmodule::layouts.new-master')

@section('title', 'Call tests')

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">Call tests</h2>
                    <div class="trn-meta mt-1">The caller speaks Urdu, Hindi, and English. You answer out loud, then the result scores the skills on that test.</div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Test</th>
                                <th>Calls</th>
                                <th>Result</th>
                                <th>Deadline</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($assignments as $assignment)
                                @php $rollup = $assignment->rollup(); @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $assignment->test->name }}</div>
                                        <div class="trn-meta">{{ $rollup['total'] }} {{ $rollup['total'] === 1 ? 'scenario' : 'scenarios' }}</div>
                                    </td>
                                    <td>
                                        <div class="lms-progress-cell">
                                            <div class="lms-bar {{ $rollup['complete'] ? 'is-done' : '' }}"><span style="width: {{ $rollup['total'] ? (int) round(($rollup['done'] / $rollup['total']) * 100) : 0 }}%"></span></div>
                                            <span class="trn-meta">{{ $rollup['done'] }} of {{ $rollup['total'] }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @include('adminmodule::admin.training._badge', ['text' => $rollup['label'], 'tone' => $rollup['tone']])
                                        @if($rollup['earned'] !== null)
                                            <div class="trn-meta mt-1">{{ $rollup['earned'] }} / {{ $rollup['marks'] }} · pass {{ $assignment->test->pass_mark }}</div>
                                        @elseif($rollup['overall'] !== null)
                                            <div class="trn-meta mt-1">{{ $rollup['overall'] }} / pass {{ $assignment->test->pass_mark }}</div>
                                        @endif
                                    </td>
                                    <td class="trn-meta">{{ $assignment->due_at ? $assignment->due_at->format('j M Y') : '—' }}</td>
                                    <td class="text-end">
                                        <a class="btn btn--primary btn-sm" href="{{ route('admin.training.calls.show', $assignment) }}">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">No call tests are assigned to you yet.</td>
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
