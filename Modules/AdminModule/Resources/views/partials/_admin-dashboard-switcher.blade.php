@php
    $active = $active ?? 'work';
    $turboAttrs = admin_uses_partial_nav()
        ? 'data-turbo-frame="admin-main" data-turbo-action="advance"'
        : '';
@endphp

@push('css_or_js')
<style>
    .admin-dashboard-switcher {
        margin-bottom: 0;
        flex: 0 0 auto;
        width: fit-content;
        max-width: 100%;
    }
    .admin-dashboard-switcher-pills {
        display: inline-flex; align-items: center; gap: 4px; flex-wrap: nowrap;
        width: fit-content;
        max-width: 100%;
    }
    .admin-dashboard-switcher-pill {
        display: inline-flex; align-items: center; justify-content: center;
        flex: 0 0 auto;
        width: auto;
        border: 1px solid #d1d5db; background: #fff; color: #475569;
        padding: 2px 10px; border-radius: 999px;
        font-size: 10px; font-weight: 600; line-height: 1.4;
        text-decoration: none; white-space: nowrap;
        transition: background .12s, border-color .12s, color .12s;
    }
    .admin-dashboard-switcher-pill:hover {
        color: #43466e; border-color: #c7cbe0; background: #eef0f6;
        text-decoration: none;
    }
    .admin-dashboard-switcher-pill.is-active {
        background: #43466e; color: #fff; border-color: #43466e;
    }
    .admin-dashboard-switcher-pill.is-active:hover {
        background: #363856; color: #fff; border-color: #363856;
    }
</style>
@endpush

<div class="admin-dashboard-switcher">
    <nav class="admin-dashboard-switcher-pills" aria-label="{{ translate('dashboard') }}">
        <a href="{{ route('admin.dashboard') }}"
           class="admin-dashboard-switcher-pill {{ $active === 'work' ? 'is-active' : '' }}"
           {!! $turboAttrs !!}>
            {{ translate('Work') }}
        </a>
        <a href="{{ route('admin.dashboard.operations') }}"
           class="admin-dashboard-switcher-pill {{ $active === 'operations' ? 'is-active' : '' }}"
           {!! $turboAttrs !!}>
            {{ translate('Operations') }}
        </a>
        <a href="{{ route('admin.dashboard.finance') }}"
           class="admin-dashboard-switcher-pill {{ $active === 'finance' ? 'is-active' : '' }}"
           {!! $turboAttrs !!}>
            {{ translate('Finance') }}
        </a>
    </nav>
</div>
