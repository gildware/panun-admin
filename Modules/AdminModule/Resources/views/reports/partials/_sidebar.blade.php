@php
    $reportsSections = $reportsSections ?? \App\Support\AdminReportsRegistry::visibleSections();
    $match = \App\Support\AdminReportsRegistry::match();
    $activeReportsSectionKey = $activeReportsSectionKey ?? ($match['section_key'] ?? \App\Support\AdminReportsRegistry::defaultSectionKey());
@endphp

<aside class="settings-module-sidebar" aria-label="{{ translate('Reports') }}">
    <div class="settings-module-sidebar-head">
        <a href="{{ route('admin.reports.index') }}"
           class="settings-module-home {{ request()->routeIs('admin.reports.index') ? 'is-active' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            <span class="material-symbols-outlined">assessment</span>
            {{ translate('Reports') }}
        </a>
    </div>

    <nav class="settings-module-nav">
        @foreach($reportsSections as $section)
            <div class="settings-module-nav-group {{ $activeReportsSectionKey === $section['key'] ? 'is-open' : '' }}">
                <a href="{{ route('admin.reports.index', ['section' => $section['key']]) }}"
                   class="settings-module-nav-link {{ $activeReportsSectionKey === $section['key'] ? 'is-active' : '' }}"
                   @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                    <span class="material-symbols-outlined">{{ $section['icon'] }}</span>
                    {{ $section['label'] }}
                </a>

                <div class="settings-module-nav-sub">
                    @foreach($section['items'] as $item)
                        <a href="{{ $item['url'] }}"
                           class="settings-module-nav-sublink {{ \App\Support\AdminReportsRegistry::itemIsActive($item) ? 'is-active' : '' }}"
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
