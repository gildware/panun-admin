@extends('adminmodule::layouts.master')

@section('title', translate('Profile_Update_Requests'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h2 class="page-title mb-0">{{ translate('Profile_Update_Request') }}</h2>
                @if($changeRequest->status === \Modules\ProviderManagement\Entities\ProviderChangeRequest::STATUS_PENDING)
                    @can('onboarding_request_approve_or_deny')
                        @if($changeRequest->change_type === 'services' && count($proposedChanges) > 0)
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-soft--danger profile_change_deny"
                                        data-id="{{ $changeRequest->id }}">{{ translate('Deny_All') }}</button>
                                <button type="button" class="btn btn--success" id="submit_service_review">
                                    {{ translate('Submit_Review') }}
                                </button>
                            </div>
                        @else
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-soft--danger profile_change_deny"
                                        data-id="{{ $changeRequest->id }}">{{ translate('Deny') }}</button>
                                <button type="button" class="btn btn--success profile_change_approve"
                                        data-id="{{ $changeRequest->id }}">{{ translate('Accept') }}</button>
                            </div>
                        @endif
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
                    @if($changeRequest->change_type === 'services' && $changeRequest->status === \Modules\ProviderManagement\Entities\ProviderChangeRequest::STATUS_PENDING && count($proposedChanges) > 0)
                        <p class="text-muted mb-0 small">{{ translate('Review_each_service_change_help') }}</p>
                    @endif
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
                                    @if($changeRequest->change_type === 'services' && $changeRequest->status === \Modules\ProviderManagement\Entities\ProviderChangeRequest::STATUS_PENDING)
                                        <th style="min-width:160px;">{{ translate('Decision') }}</th>
                                    @endif
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($proposedChanges as $change)
                                    <tr @if($changeRequest->change_type === 'services' && !empty($change['sub_category_id'])) class="service-change-row" data-sub-category-id="{{ $change['sub_category_id'] }}" @endif>
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
                                        @if($changeRequest->change_type === 'services' && $changeRequest->status === \Modules\ProviderManagement\Entities\ProviderChangeRequest::STATUS_PENDING)
                                            <td>
                                                @if(!empty($change['sub_category_id']))
                                                    <select class="form-select form-select-sm service-change-decision">
                                                        <option value="approve" selected>{{ translate('Accept') }}</option>
                                                        <option value="deny">{{ translate('Deny') }}</option>
                                                    </select>
                                                @endif
                                            </td>
                                        @endif
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

        $('#submit_service_review').on('click', function () {
            const decisions = [];
            $('.service-change-row').each(function () {
                decisions.push({
                    sub_category_id: $(this).data('sub-category-id'),
                    approved: $(this).find('.service-change-decision').val() === 'approve'
                });
            });

            if (!decisions.length) {
                return;
            }

            const approvedCount = decisions.filter((item) => item.approved).length;
            const deniedCount = decisions.length - approvedCount;
            let confirmText = '{{ translate('Submit_service_review_confirm') }}';
            if (deniedCount > 0 && approvedCount > 0) {
                confirmText = '{{ translate('Submit_partial_service_review_confirm') }}';
            } else if (approvedCount === 0) {
                confirmText = '{{ translate('Deny_all_service_changes_confirm') }}';
            }

            Swal.fire({
                title: "{{ translate('are_you_sure') }}?",
                text: confirmText,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'var(--bs-secondary)',
                confirmButtonColor: 'var(--bs-primary)',
                cancelButtonText: '{{ translate('cancel') }}',
                confirmButtonText: '{{ translate('yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (!result.value) {
                    return;
                }

                $.ajax({
                    url: '{{ route('admin.provider.profile_change_review', ['id' => $changeRequest->id]) }}',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: '{{ csrf_token() }}',
                        decisions: decisions
                    },
                    success: function (data) {
                        toastr.success(data.message, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON?.message || '{{ translate('failed_to_update') }}';
                        toastr.error(message, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                });
            });
        });
    </script>
@endpush
