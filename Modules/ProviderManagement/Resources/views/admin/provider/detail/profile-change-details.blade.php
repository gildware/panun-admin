@extends('adminmodule::layouts.master')

@section('title', translate('Profile_Update_Requests'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h2 class="page-title mb-0">{{ translate('Profile_Update_Request') }}</h2>
                @if($changeRequest->status === \Modules\ProviderManagement\Entities\ProviderChangeRequest::STATUS_PENDING)
                    @can('onboarding_request_approve_or_deny')
                        @if(count($proposedChanges) > 0)
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-soft--danger profile_change_deny"
                                        data-id="{{ $changeRequest->id }}">{{ translate('Deny_All') }}</button>
                                <button type="button" class="btn btn--success" id="submit_change_review">
                                    {{ translate('Submit_Review') }}
                                </button>
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
                    @if($changeRequest->status === \Modules\ProviderManagement\Entities\ProviderChangeRequest::STATUS_PENDING && count($proposedChanges) > 0)
                        <p class="text-muted mb-0 small">{{ translate('Review_each_change_help') }}</p>
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
                                    @if($changeRequest->status === \Modules\ProviderManagement\Entities\ProviderChangeRequest::STATUS_PENDING)
                                        @can('onboarding_request_approve_or_deny')
                                            <th style="min-width:200px;">{{ translate('Action') }}</th>
                                        @endcan
                                    @endif
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($proposedChanges as $change)
                                    <tr class="change-review-row"
                                        data-field-key="{{ $change['field_key'] ?? '' }}"
                                        data-decision="approve">
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
                                        @if($changeRequest->status === \Modules\ProviderManagement\Entities\ProviderChangeRequest::STATUS_PENDING)
                                            @can('onboarding_request_approve_or_deny')
                                                <td>
                                                    @if(!empty($change['field_key']))
                                                        <div class="table-actions justify-content-center gap-2 flex-nowrap">
                                                            <button type="button"
                                                                    class="btn btn-soft--danger btn-sm change-review-deny">
                                                                {{ translate('Deny') }}
                                                            </button>
                                                            <button type="button"
                                                                    class="btn btn--success btn-sm change-review-accept">
                                                                {{ translate('Accept') }}
                                                            </button>
                                                        </div>
                                                    @endif
                                                </td>
                                            @endcan
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

        function setRowDecision($row, decision) {
            $row.attr('data-decision', decision);
            const $accept = $row.find('.change-review-accept');
            const $deny = $row.find('.change-review-deny');

            if (decision === 'approve') {
                $accept.removeClass('btn-soft--success').addClass('btn--success');
                $deny.removeClass('btn--danger').addClass('btn-soft--danger');
            } else {
                $deny.removeClass('btn-soft--danger').addClass('btn--danger');
                $accept.removeClass('btn--success').addClass('btn-soft--success');
            }
        }

        $('.change-review-row').each(function () {
            setRowDecision($(this), 'approve');
        });

        $(document).on('click', '.change-review-accept', function () {
            setRowDecision($(this).closest('.change-review-row'), 'approve');
        });

        $(document).on('click', '.change-review-deny', function () {
            setRowDecision($(this).closest('.change-review-row'), 'deny');
        });

        $('.profile_change_deny').on('click', function () {
            let id = $(this).data('id');
            let route = '{{ route('admin.provider.profile_change_update', ['id' => ':id', 'status' => 'deny']) }}'.replace(':id', id);
            route_alert_reload(route, '{{ translate('want_to_deny') }}', true);
        });

        $('#submit_change_review').on('click', function () {
            const decisions = [];
            $('.change-review-row').each(function () {
                const fieldKey = $(this).attr('data-field-key');
                if (!fieldKey) {
                    return;
                }

                decisions.push({
                    field_key: String(fieldKey),
                    approved: $(this).attr('data-decision') === 'approve' ? 1 : 0
                });
            });

            if (!decisions.length) {
                return;
            }

            const approvedCount = decisions.filter((item) => Number(item.approved) === 1).length;
            const deniedCount = decisions.length - approvedCount;
            let confirmText = '{{ translate('Submit_change_review_confirm') }}';
            if (deniedCount > 0 && approvedCount > 0) {
                confirmText = '{{ translate('Submit_partial_change_review_confirm') }}';
            } else if (approvedCount === 0) {
                confirmText = '{{ translate('Deny_all_changes_confirm') }}';
            }

            const payload = {
                _token: '{{ csrf_token() }}'
            };
            decisions.forEach(function (item, index) {
                payload['decisions[' + index + '][field_key]'] = item.field_key;
                payload['decisions[' + index + '][approved]'] = item.approved;
            });

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
                    data: payload,
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
