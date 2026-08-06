@php
    $marketingSections = $marketingSections ?? \App\Support\AdminMarketingRegistry::visibleSections();
    $match = \App\Support\AdminMarketingRegistry::match();
    $activeMarketingSectionKey = $activeMarketingSectionKey ?? ($match['section_key'] ?? 'provider_advertisement');
@endphp

<aside class="settings-module-sidebar" aria-label="{{ translate('Marketing') }}">
    <div class="settings-module-sidebar-head">
        <a href="{{ route('admin.marketing.index') }}"
           class="settings-module-home {{ request()->routeIs('admin.marketing.index') ? 'is-active' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            <span class="material-symbols-outlined">campaign</span>
            {{ translate('Marketing') }}
        </a>
    </div>

    <nav class="settings-module-nav">
        @foreach($marketingSections as $section)
            <div class="settings-module-nav-group {{ $activeMarketingSectionKey === $section['key'] ? 'is-open' : '' }}">
                <a href="{{ route('admin.marketing.index', ['section' => $section['key']]) }}"
                   class="settings-module-nav-link {{ $activeMarketingSectionKey === $section['key'] ? 'is-active' : '' }}"
                   @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                    <span class="material-symbols-outlined">{{ $section['icon'] }}</span>
                    {{ $section['label'] }}
                </a>

                <div class="settings-module-nav-sub">
                    @foreach($section['items'] as $item)
                        <a href="{{ $item['url'] }}"
                           class="settings-module-nav-sublink {{ \App\Support\AdminMarketingRegistry::itemIsActive($item) ? 'is-active' : '' }}"
                           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <a href="{{ route('admin.dashboard') }}"
       class="settings-module-back"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        <span class="material-symbols-outlined">arrow_back</span>
        {{ translate('dashboard') }}
    </a>
</aside>
