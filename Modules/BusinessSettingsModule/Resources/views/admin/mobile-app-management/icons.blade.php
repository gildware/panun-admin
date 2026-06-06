@extends('adminmodule::layouts.new-master')

@section('title', translate('Icons_and_images'))

@push('css_or_js')
    <style>
        .mai-preview-light { background: #f4f6f8; border: 1px solid #e2e8f0; }
        .mai-preview-dark { background: #1a1d21; border: 1px solid #2d3339; color: #fff; }
        .mai-preview-dark .opacity-75, .mai-preview-dark .opacity-50 { color: #cbd5e1; }
        .mai-icon-upload .upload-file-new__wrapper { max-width: 120px; margin: 0 auto; }
        .mai-icon-field .badge { font-weight: 500; }
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

            <ul class="nav nav--tabs nav--tabs__style2 flex-wrap gap-2 mb-3">
                @foreach($tabs as $t)
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === $t['id'] ? 'active' : '' }}"
                           href="{{ route('admin.mobile-app-management.icons', ['tab' => $t['id']]) }}">
                            {{ $t['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <form action="{{ route('admin.mobile-app-management.icons.update') }}" method="POST" enctype="multipart/form-data" id="maiIconsForm">
                @csrf
                <input type="hidden" name="tab" value="{{ $tab }}">

                <div class="card mb-3">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <strong>
                            @if($tab === 'logos')
                                {{ translate('App_logos') }}
                            @elseif($tab === 'customer')
                                {{ translate('Customer_icons') }}
                            @else
                                {{ translate('Provider_icons') }}
                            @endif
                        </strong>
                        @if($tab === 'logos')
                            <span class="fz-12 text-muted mb-0">{{ translate('Customer_and_provider_app_logos') }}</span>
                        @elseif($tab === 'customer')
                            <span class="fz-12 text-muted mb-0">{{ translate('More_menu_icons') }} — {{ translate('Customer_app') }}</span>
                        @else
                            <span class="fz-12 text-muted mb-0">{{ translate('More_menu_icons') }} — {{ translate('Provider_app') }}</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @php $logoSectionApp = null; @endphp
                            @forelse($tabIconItems as $item)
                                @if($tab === 'logos' && $logoSectionApp !== $item['appKey'])
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

                <button type="submit" class="btn btn-primary">{{ translate('save') }}</button>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ asset('assets/common/js/single-image-upload.js') }}"></script>
@endpush
