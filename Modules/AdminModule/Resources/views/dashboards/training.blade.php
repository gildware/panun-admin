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
                    <p>{{ is_super_admin() ? 'My training holds the programs assigned to you. Call tests are scored phone calls. Training setup holds program creation, assignment, and live monitoring.' : 'Programs and call tests assigned to you, with progress and result on each one.' }}</p>
                </div>

                <h2 class="h6 text-uppercase text-muted mb-0" style="letter-spacing: .06em">My training</h2>
                <div class="ws-dash-grid ws-dash-grid--3">
                    <article class="ws-dash-card ws-dash-guide">
                        <h2>My training</h2>
                        <p>Programs assigned to you, with progress and result on each one.</p>
                        <div class="ws-dash-stat">
                            <div class="value">List</div>
                            <div class="sub">progress and result</div>
                        </div>
                        <a href="{{ route('admin.training.index') }}" @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>Open my training</a>
                    </article>
                    <article class="ws-dash-card ws-dash-guide">
                        <h2>Call tests</h2>
                        <p>Answer a mock customer or provider call in the browser, then see the skill result.</p>
                        <div class="ws-dash-stat">
                            <div class="value">Call</div>
                            <div class="sub">then the result</div>
                        </div>
                        <a href="{{ route('admin.training.calls.index') }}" @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>Open call tests</a>
                    </article>
                </div>

                @if(is_super_admin())
                    <h2 class="h6 text-uppercase text-muted mb-0 mt-2" style="letter-spacing: .06em">Training setup</h2>
                    <div class="ws-dash-grid ws-dash-grid--3">
                        <article class="ws-dash-card ws-dash-guide">
                            <h2>Programs</h2>
                            <p>Create a program, add the lesson and tests, then publish it.</p>
                            <div class="ws-dash-stat">
                                <div class="value">Create</div>
                                <div class="sub">then publish</div>
                            </div>
                            <a href="{{ route('admin.training.manage') }}" @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>Open programs</a>
                        </article>
                        <article class="ws-dash-card ws-dash-guide">
                            <h2>Assign</h2>
                            <p>Give a published program to an employee.</p>
                            <div class="ws-dash-stat">
                                <div class="value">Assign</div>
                                <div class="sub">published only</div>
                            </div>
                            <a href="{{ route('admin.training.assign') }}" @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>Open assign</a>
                        </article>
                        <article class="ws-dash-card ws-dash-guide">
                            <h2>Live</h2>
                            <p>See who is in a lesson, who has not started, and who has finished.</p>
                            <div class="ws-dash-stat">
                                <div class="value">Live</div>
                                <div class="sub">right now</div>
                            </div>
                            <a href="{{ route('admin.training.live') }}" @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>Open live</a>
                        </article>
                        <article class="ws-dash-card ws-dash-guide">
                            <h2>Call tests</h2>
                            <p>Pick one scenario and the skills, publish the test, and assign it.</p>
                            <div class="ws-dash-stat">
                                <div class="value">Set up</div>
                                <div class="sub">then assign</div>
                            </div>
                            <a href="{{ route('admin.training.calls.manage') }}" @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>Set up call tests</a>
                        </article>
                    </div>
                @endif

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
