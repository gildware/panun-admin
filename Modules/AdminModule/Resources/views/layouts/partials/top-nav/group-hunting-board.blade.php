@can('lead_view')
<div class="top-nav-item">
    <a href="{{ route('admin.lead.hunting-board.index') }}"
       class="top-nav-trigger {{ request()->is('admin/lead/hunting-board*') ? 'active-menu' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'travel_explore'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Hunting_Board'),
            'count' => $hunting_board_menu_count ?? 0,
        ])
    </a>
</div>
@endcan
