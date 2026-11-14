<div class="top-nav-item">
    <a href="{{ route('admin.open-work.index') }}"
       class="top-nav-trigger {{ request()->is('admin/open-work*') ? 'active-menu' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'pending_actions'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Open_Work'),
        ])
    </a>
</div>
