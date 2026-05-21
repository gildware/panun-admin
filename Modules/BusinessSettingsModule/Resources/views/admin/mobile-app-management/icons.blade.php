@extends('adminmodule::layouts.new-master')

@section('title', translate('Icons_and_images'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-20">
                <div>
                    <h2 class="page-title mb-1">{{ translate('Icons_and_images') }}</h2>
                    <p class="fz-12 text-muted mb-0">{{ translate('Mobile_app_icons_and_logos_hint') }}</p>
                </div>
            </div>

            <form action="{{ route('admin.mobile-app-management.icons.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @foreach($groups as $groupKey => $group)
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong>
                                @if($groupKey === 'logos')
                                    {{ translate('App_logos') }}
                                @else
                                    {{ translate('More_menu_icons') }}
                                @endif
                            </strong>
                        </div>
                        <div class="card-body">
                            @foreach(['customer' => translate('Customer_app'), 'provider' => translate('Provider_app')] as $appKey => $appLabel)
                                @if(!empty($group[$appKey]))
                                    <h5 class="mb-3 mt-2">{{ $appLabel }}</h5>
                                    <div class="row g-3 mb-4">
                                        @foreach($group[$appKey] as $def)
                                            @php
                                                $field = "icon_{$appKey}_{$def['key']}";
                                                $preview = $iconPreviews[$appKey][$def['key']] ?? null;
                                            @endphp
                                            <div class="col-md-6 col-lg-4">
                                                <label class="form-label fw-semibold">{{ $def['label'] }}</label>
                                                @if($preview)
                                                    <div class="mb-2">
                                                        <img src="{{ $preview }}" alt="" class="rounded border" style="max-height:48px;max-width:120px;object-fit:contain;">
                                                    </div>
                                                @endif
                                                <input type="file" name="{{ $field }}" class="form-control"
                                                       accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
                                                <div class="form-text">{{ translate('Recommended_square_png') }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <button type="submit" class="btn btn-primary">{{ translate('save') }}</button>
            </form>
        </div>
    </div>
@endsection
