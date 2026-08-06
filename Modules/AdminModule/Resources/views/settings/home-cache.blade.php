@extends('adminmodule::layouts.new-master')

@section('title', translate('Reset_home_cache'))

@section('content')
    <div class="settings-module">
        @include('adminmodule::settings.partials._sidebar', [
            'settingsSections' => $settingsSections,
            'activeSettingsSectionKey' => $activeSettingsSectionKey,
        ])

        <div class="settings-module-main">
            <div class="settings-module-header">
                <h1 class="settings-module-title">{{ translate('Reset_home_cache') }}</h1>
                <p class="settings-module-desc">{{ translate('Reset_and_rebuild_customer_home_cache') }}</p>
            </div>

            <div class="card">
                <div class="card-body">
                    @include('adminmodule::layouts.partials._home-cache-reset-btn', [
                        'homeCacheNeedsReset' => $homeCacheNeedsReset ?? false,
                        'wrapperClass' => 'home-cache-reset-wrap home-cache-reset-wrap--settings',
                        'buttonClass' => 'btn btn--primary d-inline-flex align-items-center gap-2',
                        'labelClass' => '',
                        'formClass' => 'd-inline',
                        'reminderClass' => '',
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection
