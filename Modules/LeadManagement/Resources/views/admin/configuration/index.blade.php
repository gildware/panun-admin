@extends('adminmodule::layouts.new-master')

@section('title', translate('Lead_Configuration'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-2">
                        <h2 class="page-title mb-1">{{ translate('Lead_Configuration') }}</h2>
                    </div>

                    <ul class="nav nav--tabs mb-3" id="leadConfigTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active"
                                    id="general-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#general-tab-pane"
                                    role="tab"
                                    aria-controls="general-tab-pane"
                                    aria-selected="true"
                                    href="#general-tab-pane">
                                {{ __('General') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link"
                                    id="customer-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#customer-tab-pane"
                                    role="tab"
                                    aria-controls="customer-tab-pane"
                                    aria-selected="false"
                                    href="#customer-tab-pane">
                                {{ __('Customer Related') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link"
                                    id="provider-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#provider-tab-pane"
                                    role="tab"
                                    aria-controls="provider-tab-pane"
                                    aria-selected="false"
                                    href="#provider-tab-pane">
                                {{ __('Provider Related') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link"
                                    id="future-customer-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#future-customer-tab-pane"
                                    role="tab"
                                    aria-controls="future-customer-tab-pane"
                                    aria-selected="false"
                                    href="#future-customer-tab-pane">
                                {{ __('Future Customer Related') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link"
                                    id="invalid-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#invalid-tab-pane"
                                    role="tab"
                                    aria-controls="invalid-tab-pane"
                                    aria-selected="false"
                                    href="#invalid-tab-pane">
                                {{ __('Invalid Related') }}
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="leadConfigTabsContent">
                        <div class="tab-pane fade show active" id="general-tab-pane" role="tabpanel" aria-labelledby="general-tab" tabindex="0">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Lead_Source'),
                                        'type' => 'source',
                                        'items' => $sources,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Ad_Types'),
                                        'type' => 'ad_source',
                                        'items' => $adSources,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Districts'),
                                        'type' => 'district',
                                        'items' => $districts,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Outbound_Enquiry_Status'),
                                        'type' => 'outbound_enquiry_status',
                                        'items' => $outboundEnquiryStatuses ?? collect(),
                                    ])
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="customer-tab-pane" role="tabpanel" aria-labelledby="customer-tab" tabindex="0">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Customer_lead_status'),
                                        'type' => 'customer_lead_status',
                                        'items' => $customerLeadStatuses,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Customer_lead_tags'),
                                        'type' => 'customer_lead_tag',
                                        'items' => $customerLeadTags,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Customer_cancellation_reasons'),
                                        'type' => 'cancellation_reason',
                                        'items' => $cancellationReasons,
                                    ])
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="provider-tab-pane" role="tabpanel" aria-labelledby="provider-tab" tabindex="0">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Provider_lead_status'),
                                        'type' => 'provider_lead_status',
                                        'items' => $providerLeadStatuses,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Provider_cancellation_reasons'),
                                        'type' => 'provider_cancellation_reason',
                                        'items' => $providerCancellationReasons,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Provider_Checklist_Items'),
                                        'type' => 'provider_checklist_item',
                                        'items' => $providerChecklistItems,
                                    ])
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="future-customer-tab-pane" role="tabpanel" aria-labelledby="future-customer-tab" tabindex="0">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Future_customer_reasons'),
                                        'type' => 'future_customer_reason',
                                        'items' => $futureCustomerReasons,
                                    ])
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="invalid-tab-pane" role="tabpanel" aria-labelledby="invalid-tab" tabindex="0">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Invalid_reasons'),
                                        'type' => 'invalid_reason',
                                        'items' => $invalidReasons,
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script>
        (function () {
            const storageKey = 'lead_config_active_tab';

            document.addEventListener('DOMContentLoaded', function () {
                const tabTriggers = document.querySelectorAll('#leadConfigTabs [data-bs-toggle="tab"]');

                // Save active tab on change
                tabTriggers.forEach(function (triggerEl) {
                    triggerEl.addEventListener('shown.bs.tab', function (event) {
                        const target = event.target.getAttribute('data-bs-target');
                        if (target) {
                            localStorage.setItem(storageKey, target);
                        }
                    });
                });

                // Restore last active tab
                const savedTarget = localStorage.getItem(storageKey);
                if (savedTarget) {
                    const savedTrigger = document.querySelector('#leadConfigTabs [data-bs-target="' + savedTarget + '"]');
                    if (savedTrigger) {
                        const tab = new bootstrap.Tab(savedTrigger);
                        tab.show();
                    }
                }
            });
        })();
    </script>
@endpush

