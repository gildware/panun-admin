@extends('adminmodule::layouts.new-master')

@section('title', translate('Task_Board_Trash'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h2 class="mb-1">{{ translate('Deleted_Tickets') }}</h2>
                    <p class="text-muted mb-0">{{ translate('Only_super_admin_can_restore') }}</p>
                </div>
                <a href="{{ route('admin.task-board.index') }}" class="btn btn-outline-primary">{{ translate('Back_to_Board') }}</a>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                            <tr>
                                <th>{{ translate('Title') }}</th>
                                <th>{{ translate('Column') }}</th>
                                <th>{{ translate('Deleted_at') }}</th>
                                <th>{{ translate('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($tickets as $ticket)
                                <tr>
                                    <td>{{ $ticket->title }}</td>
                                    <td>{{ $ticket->column?->name ?? '—' }}</td>
                                    <td>{{ optional($ticket->deleted_at)->format('d M Y H:i') }}</td>
                                    <td>
                                        <form method="post" action="{{ route('admin.task-board.tickets.restore', $ticket->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">{{ translate('Restore') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">{{ translate('No_deleted_tickets') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($tickets->hasPages())
                    <div class="card-footer">{{ $tickets->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
