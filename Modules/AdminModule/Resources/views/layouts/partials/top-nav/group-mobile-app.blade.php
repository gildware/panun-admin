@canany(['mobile_app_ai_view', 'mobile_app_home_page_view', 'mobile_app_icons_view', 'ai_configuration_view'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('mobile_app'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        {{ translate('Mobile App') }} <span class="material-icons">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @canany(['mobile_app_ai_view', 'ai_configuration_view'])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.mobile-app-management.ai'),
                'label' => translate('AI'),
                'active' => request()->is('admin/mobile-app-management/ai*'),
            ])
        @endcanany

        @can('mobile_app_home_page_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.mobile-app-management.settings'),
                'label' => translate('App_Features'),
                'active' => request()->is('admin/mobile-app-management/settings*'),
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.mobile-app-management.home-page'),
                'label' => translate('Home_Page'),
                'active' => request()->is('admin/mobile-app-management/home-page*'),
            ])
        @endcan

        @can('mobile_app_icons_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.mobile-app-management.icons'),
                'label' => translate('Icons_and_images'),
                'active' => request()->is('admin/mobile-app-management/icons*'),
            ])
        @endcan
    </div>
</div>
@endcanany
