@canany(['advertisement_view', 'campaign_view', 'whatsapp_marketing_campaign_view', 'whatsapp_marketing_bulk_view'])
    @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('Marketing')])
    @include('adminmodule::layouts.partials.top-nav._link', [
        'href' => route('admin.marketing.index'),
        'label' => translate('Marketing'),
        'active' => request()->routeIs('admin.marketing.*') || \App\Support\AdminNavRegistry::groupIsActive('marketing'),
    ])
@endcanany
