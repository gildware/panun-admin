@extends('adminmodule::layouts.new-master')

@section('title', translate('Icons_and_images'))

@push('css_or_js')
    <style>
        .mai-preview-light { background: #f4f6f8; border: 1px solid #e2e8f0; }
        .mai-preview-dark { background: #1a1d21; border: 1px solid #2d3339; color: #fff; }
        .mai-preview-dark .opacity-75, .mai-preview-dark .opacity-50 { color: #cbd5e1; }
        .mai-icon-upload .upload-file-new__wrapper { max-width: 120px; margin: 0 auto; }
        .mai-icon-field .badge { font-weight: 500; }
        .mai-icons-tab-panel { display: none; }
        .mai-icons-tab-panel.is-active { display: block; }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-20">
                <div>
                    <h2 class="page-title mb-1">{{ translate('Icons_and_images') }}</h2>
                    <p class="fz-12 text-muted mb-0">{{ translate('Mobile_app_icons_and_logos_hint') }}</p>
                </div>
            </div>

            <ul class="nav nav--tabs nav--tabs__style2 flex-wrap gap-2 mb-3" id="maiIconsTabs">
                @foreach($tabs as $t)
                    <li class="nav-item">
                        <button type="button"
                                class="nav-link mai-icons-tab-btn {{ $tab === $t['id'] ? 'active' : '' }}"
                                data-tab="{{ $t['id'] }}">
                            {{ $t['label'] }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <form action="{{ route('admin.mobile-app-management.icons.update') }}" method="POST" enctype="multipart/form-data" id="maiIconsForm">
                @csrf
                <input type="hidden" name="tab" id="maiIconsActiveTab" value="{{ $tab }}">

                @foreach($tabs as $t)
                    @php
                        $tabId = $t['id'];
                        $tabIconItems = $tabIconItemsByTab[$tabId] ?? [];
                        $iconPreviews = $iconPreviewsByTab[$tabId] ?? ['customer' => [], 'provider' => []];
                    @endphp
                    <div class="mai-icons-tab-panel {{ $tab === $tabId ? 'is-active' : '' }}" data-tab-panel="{{ $tabId }}">
                        <div class="card mb-3">
                            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <strong>
                                    @if($tabId === 'logos')
                                        {{ translate('App_logos') }}
                                    @elseif($tabId === 'customer')
                                        {{ translate('Customer_icons') }}
                                    @else
                                        {{ translate('Provider_icons') }}
                                    @endif
                                </strong>
                                @if($tabId === 'logos')
                                    <span class="fz-12 text-muted mb-0">{{ translate('Customer_and_provider_app_logos') }}</span>
                                @elseif($tabId === 'customer')
                                    <span class="fz-12 text-muted mb-0">{{ translate('More_menu_icons') }} — {{ translate('Customer_app') }}</span>
                                @else
                                    <span class="fz-12 text-muted mb-0">{{ translate('More_menu_icons') }} — {{ translate('Provider_app') }}</span>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @php $logoSectionApp = null; @endphp
                                    @forelse($tabIconItems as $item)
                                        @if($tabId === 'logos' && $logoSectionApp !== $item['appKey'])
                                            @php $logoSectionApp = $item['appKey']; @endphp
                                            <div class="col-12 mt-2">
                                                <h5 class="mb-0 text-primary">
                                                    {{ $item['appKey'] === 'customer' ? translate('Customer_app') : translate('Provider_app') }}
                                                </h5>
                                            </div>
                                        @endif
                                        @include('businesssettingsmodule::admin.mobile-app-management.partials.icon-upload-field', [
                                            'appKey' => $item['appKey'],
                                            'def' => $item['def'],
                                            'icons' => $icons,
                                            'iconPreviews' => $iconPreviews,
                                            'iconVariants' => $iconVariants,
                                        ])
                                    @empty
                                        <div class="col-12">
                                            <p class="text-muted mb-0">{{ translate('No_icons_in_this_tab') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary">{{ translate('save') }}</button>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ asset('assets/common/js/single-image-upload.js') }}"></script>
    <script>
        "use strict";

        (function () {
            const tabButtons = document.querySelectorAll('.mai-icons-tab-btn');
            const tabPanels = document.querySelectorAll('.mai-icons-tab-panel');
            const activeTabInput = document.getElementById('maiIconsActiveTab');

            function activateTab(tabId, updateUrl) {
                tabButtons.forEach((button) => {
                    button.classList.toggle('active', button.dataset.tab === tabId);
                });

                tabPanels.forEach((panel) => {
                    panel.classList.toggle('is-active', panel.dataset.tabPanel === tabId);
                });

                if (activeTabInput) {
                    activeTabInput.value = tabId;
                }

                if (updateUrl) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tabId);
                    window.history.replaceState({}, '', url);
                }
            }

            tabButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    activateTab(this.dataset.tab, true);
                });
            });

            const initialTab = activeTabInput ? activeTabInput.value : 'logos';
            activateTab(initialTab, false);
        })();
    </script>
@endpush
