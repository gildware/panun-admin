@php
    $settingsSections = $settingsSections ?? \App\Support\AdminSettingsRegistry::visibleSections();
    $match = \App\Support\AdminSettingsRegistry::match();
    $activeSettingsSectionKey = $activeSettingsSectionKey ?? ($match['section_key'] ?? 'business');
@endphp

<aside class="settings-module-sidebar" aria-label="{{ translate('Settings') }}">
    <div class="settings-module-sidebar-head">
        <a href="{{ route('admin.settings.index') }}"
           class="settings-module-home {{ request()->routeIs('admin.settings.index') ? 'is-active' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            <span class="material-symbols-outlined">settings</span>
            {{ translate('Settings') }}
        </a>
    </div>

    <nav class="settings-module-nav">
        @foreach($settingsSections as $section)
            <div class="settings-module-nav-group {{ $activeSettingsSectionKey === $section['key'] ? 'is-open' : '' }}">
                <a href="{{ route('admin.settings.index', ['section' => $section['key']]) }}"
                   class="settings-module-nav-link {{ $activeSettingsSectionKey === $section['key'] ? 'is-active' : '' }}"
                   @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                    <span class="material-symbols-outlined">{{ $section['icon'] }}</span>
                    {{ $section['label'] }}
                </a>

                <div class="settings-module-nav-sub">
                    @foreach($section['items'] as $item)
                        <a href="{{ $item['url'] }}"
                           class="settings-module-nav-sublink {{ \App\Support\AdminSettingsRegistry::itemIsActive($item) ? 'is-active' : '' }}"
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
