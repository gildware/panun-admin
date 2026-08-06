@extends('adminmodule::layouts.new-master')

@section('title', translate('Marketing'))

@section('content')
    <div class="settings-module">
        @include('adminmodule::marketing.partials._sidebar', [
            'marketingSections' => $marketingSections,
            'activeMarketingSectionKey' => $activeMarketingSection['key'],
        ])

        <div class="settings-module-main">
            <div class="settings-module-header">
                <h1 class="settings-module-title">{{ $activeMarketingSection['label'] }}</h1>
                <p class="settings-module-desc">{{ translate('Marketing') }}</p>
            </div>

            <div class="settings-module-grid">
                @foreach($activeMarketingSection['items'] as $item)
                    <a href="{{ $item['url'] }}"
                       class="settings-module-card"
                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                        <strong>{{ $item['label'] }}</strong>
                        <span>{{ $activeMarketingSection['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endsection
