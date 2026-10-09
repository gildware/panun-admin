@extends('adminmodule::layouts.new-master')

@section('title', 'Training')

@push('css_or_js')
    @include('adminmodule::dashboards._styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="ws-dash">
                <div class="ws-dash-head">
                    <h1>Training</h1>
                    <p>{{ count($guides) }} process guides to open and walk through.</p>
                </div>

                <div class="ws-dash-grid ws-dash-grid--3">
                    @foreach($guides as $guide)
                        <article class="ws-dash-card ws-dash-guide">
                            <h2>{{ $guide['title'] }}</h2>
                            <p>{{ $guide['subtitle'] }}</p>
                            <div class="ws-dash-stat">
                                <div class="value">{{ $guide['slides'] }}</div>
                                <div class="sub">slides</div>
                            </div>
                            <a href="{{ $guide['href'] }}">Open guide</a>
                        </article>
                    @endforeach
                    @if($showBusinessSystem)
                        <article class="ws-dash-card ws-dash-guide">
                            <h2>Business System</h2>
                            <p>Organisation, seats, and how the company is set up.</p>
                            <div class="ws-dash-stat">
                                <div class="value">Org</div>
                                <div class="sub">structure and seats</div>
                            </div>
                            <a href="{{ route('admin.dashboard.business-system') }}" data-turbo="false">Open system</a>
                        </article>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
