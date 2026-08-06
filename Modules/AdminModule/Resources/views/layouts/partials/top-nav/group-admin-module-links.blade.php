@php
    $showReports = count(\App\Support\AdminReportsRegistry::visibleSections()) > 0;
    $showMarketing = count(\App\Support\AdminMarketingRegistry::visibleSections()) > 0;
    $showSettings = count(\App\Support\AdminSettingsRegistry::visibleSections()) > 0;
@endphp
@if($showReports || $showMarketing || $showSettings)
<div class="top-nav-module-links" aria-label="{{ translate('Modules') }}">
    @if($showReports)
        @include('adminmodule::layouts.partials.top-nav.group-reports')
    @endif
    @if($showMarketing)
        @include('adminmodule::layouts.partials.top-nav.group-marketing')
    @endif
    @if($showSettings)
        @include('adminmodule::layouts.partials.top-nav.group-admin-settings-link')
    @endif
</div>
@endif
