<div class="top-nav-item">
    <a href="{{ route('admin.process-guides.index') }}"
       class="top-nav-trigger {{ request()->is('admin/process-guides*') ? 'active-menu' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'menu_book'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Process_Guides'),
        ])
    </a>
</div>
