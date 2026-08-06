@canany(['provider_view', 'provider_add', 'onboarding_request_view', 'withdraw_view', 'withdraw_add'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('providers'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        {{ translate('Providers') }} <span class="material-icons">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @can('onboarding_request_view')
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('provider_management')])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.provider.onboarding_request', ['status' => 'onboarding']),
                'label' => translate('Onboarding_Request'),
                'active' => request()->is('admin/provider/onboarding*'),
                'count' => $pending_providers + $denied_providers,
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.provider.showcase_approval', ['status' => 'pending']),
                'label' => translate('Work_Showcase_Approvals'),
                'active' => request()->is('admin/provider/showcase-approval*'),
                'count' => $pending_showcase_items,
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.provider.profile_change_request', ['status' => 'pending']),
                'label' => translate('Profile_Update_Requests'),
                'active' => request()->is('admin/provider/profile-change*'),
                'count' => $pending_profile_changes,
            ])
        @endcan

        @canany(['provider_view', 'provider_add'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('providers')])
            @can('provider_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.provider.list', ['status' => 'all']),
                    'label' => translate('Provider_List'),
                    'active' => request()->is('admin/provider/list') || request()->is('admin/provider/details*') || request()->is('admin/provider/edit*') || request()->is('admin/provider/collect-cash*'),
                ])
            @endcan
            @can('provider_add')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.provider.create'),
                    'label' => translate('Add_New_Provider'),
                    'active' => request()->is('admin/provider/create'),
                    'fullPage' => true,
                ])
            @endcan
        @endcanany

        @can('provider_feedback_config_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.provider.feedback-tags.index'),
                'label' => translate('Feedback_Configuration'),
                'active' => request()->is('admin/provider/feedback-tags*'),
            ])
        @endcan

        @canany(['withdraw_view', 'withdraw_add'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('Withdraws')])
            @can('withdraw_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.withdraw.request.list', ['status' => 'all']),
                    'label' => translate('Withdraw Requests'),
                    'active' => request()->is('admin/withdraw/request*'),
                ])
            @endcan
            @can('withdraw_add')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.withdraw.method.list'),
                    'label' => translate('Withdraw method setup'),
                    'active' => request()->is('admin/withdraw/method*') || request()->is('admin/withdraw/method/create') || request()->is('admin/withdraw/method/edit*'),
                ])
            @endcan
        @endcanany
    </div>
</div>
@endcanany
