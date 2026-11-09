@php
    $active = $active ?? 'match';
@endphp
<style>
    .wa-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        margin: 0;
        margin-left: auto;
        padding: .3rem;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: .65rem;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        flex-shrink: 0;
        align-self: flex-start;
    }
    .wa-tabs a {
        display: inline-flex;
        align-items: center;
        min-height: 2.05rem;
        padding: .3rem .8rem;
        border-radius: .45rem;
        font-size: .8rem;
        font-weight: 650;
        color: #475467;
        text-decoration: none;
        border: 1px solid transparent;
        white-space: nowrap;
        transition: background .15s ease, color .15s ease, border-color .15s ease;
    }
    .wa-tabs a:hover {
        background: #f8fafc;
        color: #101828;
    }
    .wa-tabs a.is-active {
        background: #0f766e;
        border-color: #0f766e;
        color: #fff;
    }
</style>
<nav class="wa-tabs" role="tablist" aria-label="{{ translate('Manage_Data') }}">
    <a
        href="{{ route('admin.provider.whatsapp-match.lookup') }}"
        role="tab"
        aria-selected="{{ $active === 'lookup' ? 'true' : 'false' }}"
        class="{{ $active === 'lookup' ? 'is-active' : '' }}"
    >{{ translate('Lookup_phone_numbers') }}</a>
    <a
        href="{{ route('admin.provider.whatsapp-match.groups') }}"
        role="tab"
        aria-selected="{{ $active === 'groups' ? 'true' : 'false' }}"
        class="{{ $active === 'groups' ? 'is-active' : '' }}"
    >{{ translate('Manage_WhatsApp_groups') }}</a>
    <a
        href="{{ route('admin.provider.whatsapp-match') }}"
        role="tab"
        aria-selected="{{ $active === 'match' ? 'true' : 'false' }}"
        class="{{ $active === 'match' ? 'is-active' : '' }}"
    >{{ translate('WhatsApp_Provider_Match_list') }}</a>
</nav>
