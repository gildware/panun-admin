<div class="top-nav-item">
    <a href="{{ route('admin.people.index') }}"
       class="top-nav-trigger {{ request()->is('admin/people') || request()->is('admin/people/team*') ? 'active-menu' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'badge'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => 'My workspace',
        ])
    </a>
</div>
