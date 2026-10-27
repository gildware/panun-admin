@if(admin_uses_top_nav())
    <button type="button"
            id="top-chrome-visibility-toggle"
            class="top-chrome-visibility-toggle"
            aria-expanded="true"
            data-label-hide="{{ translate('Hide_header') }}"
            data-label-show="{{ translate('Show_header') }}"
            title="{{ translate('Hide_header') }}"
            aria-label="{{ translate('Hide_header') }}">
        <span class="material-icons top-chrome-visibility-icon top-chrome-visibility-icon--hide" aria-hidden="true">menu_open</span>
        <span class="material-icons top-chrome-visibility-icon top-chrome-visibility-icon--show" aria-hidden="true">menu</span>
    </button>
    @include('adminmodule::layouts.partials._top-chrome')
@else
    @include('adminmodule::layouts.partials._header')
    @include('adminmodule::layouts.partials._aside')
@endif

@include('adminmodule::layouts.partials._home-cache-alert')
