<div class="top-nav-item">
    <a href="{{ route('admin.my-progress') }}"
       class="top-nav-trigger {{ request()->routeIs('admin.my-progress') ? 'active-menu' : '' }}"
       data-turbo="false">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'assessment'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Progress_Report'),
        ])
    </a>
</div>
