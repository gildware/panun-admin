@extends('adminmodule::layouts.master')

@section('title', translate('Profile_Update_Requests'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h2 class="page-title mb-0">{{ translate('Profile_Update_Request') }}</h2>
                @if($changeRequest->status === \Modules\ProviderManagement\Entities\ProviderChangeRequest::STATUS_PENDING)
                    @can('onboarding_request_approve_or_deny')
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-soft--danger profile_change_deny"
                                    data-id="{{ $changeRequest->id }}">{{ translate('Deny') }}</button>
                            <button type="button" class="btn btn--success profile_change_approve"
                                    data-id="{{ $changeRequest->id }}">{{ translate('Accept') }}</button>
                        </div>
                    @endcan
                @endif
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <p><strong>{{ translate('Provider') }}:</strong>
                        <a href="{{ route('admin.provider.details', [$changeRequest->provider_id, 'web_page' => 'overview']) }}">
                            {{ $changeRequest->provider?->company_name }}
                        </a>
                    </p>
                    <p><strong>{{ translate('Type') }}:</strong>
                        @php
                            $detailTypeKey = match ($changeRequest->change_type) {
                                'branding' => 'Logo_and_Cover',
                                'business_settings' => 'Business_Settings',
                                'services' => 'Services',
                                default => ucfirst(str_replace('_', ' ', $changeRequest->change_type)),
                            };
                        @endphp
                        {{ translate($detailTypeKey) }}
                    </p>
                    <p><strong>{{ translate('Requested_At') }}:</strong> {{ $changeRequest->created_at?->format('d M Y, h:i A') }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">{{ translate('Proposed_Changes') }}</h5>
                    @if(count($proposedChanges) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th style="min-width:180px;">{{ translate('field_name') }}</th>
                                    <th style="min-width:220px;">{{ translate('from') }}</th>
                                    <th style="min-width:220px;">{{ translate('to') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($proposedChanges as $change)
                                    <tr>
                                        <td class="fw-medium">{{ $change['field'] }}</td>
                                        <td>
                                            @if(($change['type'] ?? '') === 'image' && !empty($change['from_url']))
                                                <div class="d-flex flex-column gap-2">
                                                    <img src="{{ $change['from_url'] }}" alt="" class="rounded border" style="max-width:120px;max-height:120px;object-fit:cover;">
                                                    <span class="small text-muted">{{ $change['from'] }}</span>
                                                </div>
                                            @else
                                                {{ $change['from'] }}
                                            @endif
                                        </td>
                                        <td>
                                            @if(($change['type'] ?? '') === 'image' && !empty($change['to_url']))
                                                <div class="d-flex flex-column gap-2">
                                                    <img src="{{ $change['to_url'] }}" alt="" class="rounded border" style="max-width:120px;max-height:120px;object-fit:cover;">
                                                    <span class="small text-muted">{{ $change['to'] }}</span>
                                                </div>
                                            @else
                                                {{ $change['to'] }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">{{ translate('No_changes_in_this_request') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";
        $('.profile_change_deny').on('click', function () {
            let id = $(this).data('id');
            let route = '{{ route('admin.provider.profile_change_update', ['id' => ':id', 'status' => 'deny']) }}'.replace(':id', id);
            route_alert_reload(route, '{{ translate('want_to_deny') }}', true);
        });
        $('.profile_change_approve').on('click', function () {
            let id = $(this).data('id');
            let route = '{{ route('admin.provider.profile_change_update', ['id' => ':id', 'status' => 'approve']) }}'.replace(':id', id);
            route_alert_reload(route, '{{ translate('want_to_approve') }}', true);
        });
    </script>
@endpush
