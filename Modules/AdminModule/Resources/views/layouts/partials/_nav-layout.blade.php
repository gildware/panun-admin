@if(admin_uses_top_nav())
    @include('adminmodule::layouts.partials._top-chrome')
@else
    @include('adminmodule::layouts.partials._header')
    @include('adminmodule::layouts.partials._aside')
@endif

@include('adminmodule::layouts.partials._home-cache-alert')
