@if(!$configured)
    <div class="alert alert-warning mb-0">
        {{ translate('OmniDimension_not_configured_hint') }}
        <code>OMNIDIMENSION_API_KEY</code>
    </div>
@elseif($bulkError)
    <div class="alert alert-danger mb-0">
        {{ translate('Voice_bulk_campaigns_load_failed') }}
        <span class="d-block small mt-1 text-muted">{{ $bulkError }}</span>
    </div>
@else
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.voice-call.bulk.campaigns') }}" id="voice-bulk-filter-form">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                            'label' => translate('Status'),
                            'hint' => translate('Voice_field_hint_bulk_campaign_status'),
                        ])
                        <select class="form-select js-select" name="status">
                            <option value="">{{ translate('All') }}</option>
                            @foreach(['pending', 'running', 'completed', 'scheduled', 'paused', 'failed'] as $statusOption)
                                <option value="{{ $statusOption }}"
                                        {{ ($filterStatus ?? '') === $statusOption ? 'selected' : '' }}>
                                    {{ ucfirst($statusOption) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn--primary" type="submit">{{ translate('Search') }}</button>
                        <button class="btn btn--secondary voice-bulk-reset" type="button">{{ translate('Reset') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-30">
            <div class="table-responsive">
                <table class="table table-hover align-middle voice-bulk-campaigns-table">
                    <thead>
                    <tr>
                        <th>{{ translate('SL') }}</th>
                        <th>{{ translate('Campaign_name') }}</th>
                        <th>{{ translate('Date_Time') }}</th>
                        <th>{{ translate('OmniDimension_Agent') }}</th>
                        <th>{{ translate('Caller_Phone_Number') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Total_Calls') }}</th>
                        <th>{{ translate('Completed') }}</th>
                        <th>{{ translate('Pending') }}</th>
                        <th>{{ translate('Details') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($campaigns as $key => $campaign)
                        @php
                            $sl = (($bulkPage - 1) * pagination_limit()) + $key + 1;
                            $campaignId = (int) ($campaign['id'] ?? 0);
                            $canCancelCampaign = app(\Modules\LeadManagement\Services\OmniDimensionService::class)
                                ->bulkCampaignIsCancellable($campaign['status'] ?? null);
                            $statusClass = match ($campaign['status'] ?? '') {
                                'completed' => 'success',
                                'running', 'pending', 'in_progress' => 'primary',
                                'scheduled' => 'info',
                                'paused' => 'warning',
                                'failed', 'cancelled' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <tr data-campaign-id="{{ $campaignId }}">
                            <td>{{ $sl }}</td>
                            <td class="fw-medium">{{ $campaign['name'] ?: '—' }}</td>
                            <td class="text-nowrap small">{{ $campaign['create_date'] ?: '—' }}</td>
                            <td>{{ $campaign['bot_name'] ?: '—' }}</td>
                            <td class="text-nowrap">{{ $campaign['twilio_number'] ?: '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $statusClass }} text-capitalize">
                                    {{ $campaign['status'] ?: '—' }}
                                </span>
                                @if(!empty($campaign['is_scheduled']) && !empty($campaign['scheduled_datetime']))
                                    <div class="small text-muted mt-1">{{ $campaign['scheduled_datetime'] }}</div>
                                @endif
                                @if(!empty($campaign['failed_reason']))
                                    <div class="small text-danger mt-1">{{ $campaign['failed_reason'] }}</div>
                                @endif
                            </td>
                            <td>{{ (int) ($campaign['total_calls'] ?? 0) }}</td>
                            <td>{{ (int) ($campaign['completed_calls'] ?? 0) }}</td>
                            <td>{{ (int) ($campaign['total_pending_calls'] ?? 0) }}</td>
                            <td>
                                @if($campaignId > 0)
                                    <div class="d-inline-flex flex-wrap gap-1">
                                        <button type="button"
                                                class="btn btn-sm btn--primary voice-bulk-campaign-open-btn"
                                                data-campaign-id="{{ $campaignId }}">
                                            {{ translate('View') }}
                                        </button>
                                        @can('lead_outbound_enquiry_add')
                                            @if($canCancelCampaign)
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger voice-bulk-campaign-cancel-btn"
                                                        data-campaign-id="{{ $campaignId }}"
                                                        data-campaign-name="{{ $campaign['name'] ?: translate('Campaign_name') }}">
                                                    {{ translate('Cancel') }}
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                {{ translate('Voice_bulk_campaigns_empty') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($campaignsTotal > pagination_limit())
                @php
                    $totalPages = (int) ceil($campaignsTotal / pagination_limit());
                @endphp
                <nav class="d-flex justify-content-end mt-3">
                    <ul class="pagination mb-0">
                        @for($p = 1; $p <= $totalPages; $p++)
                            <li class="page-item {{ $p === $bulkPage ? 'active' : '' }}">
                                <a class="page-link voice-bulk-page-link"
                                   href="#"
                                   data-page="{{ $p }}"
                                   data-status="{{ $filterStatus ?? '' }}">{{ $p }}</a>
                            </li>
                        @endfor
                    </ul>
                </nav>
            @endif
        </div>
    </div>
@endif
