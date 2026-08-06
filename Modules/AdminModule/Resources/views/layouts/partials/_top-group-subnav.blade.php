@php($adminGroupSubmenu = $adminGroupSubmenu ?? \App\Support\AdminNavRegistry::groupSubmenu())

@if(!empty($adminGroupSubmenu['items']))
    <div class="top-group-subnav {{ is_admin_employee() ? 'top-group-subnav--dark' : '' }}" data-group="{{ $adminGroupSubmenu['group_key'] }}">
        <span class="top-group-subnav-label">{{ $adminGroupSubmenu['title'] }}</span>

        <div class="top-group-subnav-scroll">
            @foreach($adminGroupSubmenu['items'] as $item)
                <a href="{{ $item['url'] }}"
                   class="top-group-subnav-link {{ !empty($item['active']) ? 'active-menu' : '' }}"
                   @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                    {{ $item['label'] }}
                    @if(!empty($item['count']))<span class="badge-count">{{ $item['count'] }}</span>@endif
                </a>
            @endforeach
        </div>
    </div>
@endif
