@php($adminGroupSubmenu = $adminGroupSubmenu ?? \App\Support\AdminNavRegistry::groupSubmenu())

@if(!empty($adminGroupSubmenu['items']))
    <div class="top-group-subnav" data-group="{{ $adminGroupSubmenu['group_key'] }}">
        <span class="top-group-subnav-label">{{ $adminGroupSubmenu['title'] }}</span>

        <div class="top-group-subnav-scroll">
            @foreach($adminGroupSubmenu['items'] as $item)
                @php($pinKey = \App\Support\AdminNavRegistry::pinKeyForUrl($item['url']))
                <div class="top-nav-link-row top-nav-link-row--inline">
                    <a href="{{ $item['url'] }}"
                       class="top-group-subnav-link {{ !empty($item['active']) ? 'active-menu' : '' }}"
                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                        {{ $item['label'] }}
                    </a>
                    @include('adminmodule::layouts.partials.top-nav._pin-btn', ['pinKey' => $pinKey])
                </div>
            @endforeach
        </div>
    </div>
@endif
