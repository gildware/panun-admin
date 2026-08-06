@php
    $taskBoardAssignedTotal = (int) (($taskBoardAssignedCounts['total'] ?? 0));
@endphp
<div class="top-nav-item">
    <a href="{{ route('admin.task-board.index') }}"
       class="top-nav-trigger {{ request()->is('admin/task-board*') ? 'active-menu' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'view_kanban'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Task_Board'),
            'count' => $taskBoardAssignedTotal,
        ])
    </a>
</div>
