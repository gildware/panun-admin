@extends('adminmodule::layouts.new-master')

@section('title', translate('Reports'))

@section('content')
    <div class="settings-module">
        @include('adminmodule::reports.partials._sidebar', [
            'reportsSections' => $reportsSections,
            'activeReportsSectionKey' => $activeReportsSection['key'],
        ])

        <div class="settings-module-main">
            <div class="settings-module-header">
                <h1 class="settings-module-title">{{ $activeReportsSection['label'] }}</h1>
                <p class="settings-module-desc">{{ translate('Reports') }}</p>
            </div>

            <div class="settings-module-grid">
                @foreach($activeReportsSection['items'] as $item)
                    <a href="{{ $item['url'] }}"
                       class="settings-module-card"
                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                        <strong>{{ $item['label'] }}</strong>
                        <span>{{ $activeReportsSection['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endsection
