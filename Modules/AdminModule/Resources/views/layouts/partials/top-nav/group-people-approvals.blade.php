@php
    $approvalWorkspace = app(\Modules\AdminModule\Services\PeopleWorkspace::class);
    $approvalManager = auth()->user()
        && \Illuminate\Support\Facades\Schema::hasTable('people_profiles')
        && $approvalWorkspace->canReviewApprovals(auth()->user());
    $pendingApprovals = $approvalManager ? $approvalWorkspace->pendingApprovalCounts(auth()->user())['total'] : 0;
@endphp
@if($approvalManager)
    <div class="top-nav-item{{ $pendingApprovals > 0 ? ' top-nav-item--corner-badge' : '' }}">
        <a href="{{ route('admin.people.approvals') }}"
           class="top-nav-trigger {{ request()->routeIs('admin.people.approvals') ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'fact_check'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'Approval Request',
                'count' => $pendingApprovals,
            ])
        </a>
    </div>
@endif
