@if($runs->isEmpty())
    <p class="text-muted mb-0 text-center py-3">{{ translate('Voice_cron_no_executions') }}</p>
@else
    <div class="table-responsive voice-call-table-wrap">
        <table class="table table-sm table-hover align-middle mb-0 voice-call-data-table">
            <thead>
            <tr>
                <th>{{ translate('Started_at') }}</th>
                <th>{{ translate('Cron_job') }}</th>
                <th>{{ translate('Trigger') }}</th>
                <th>{{ translate('Status') }}</th>
                <th>{{ translate('Matched') }}</th>
                <th>{{ translate('Dispatched') }}</th>
                <th>{{ translate('Duration') }}</th>
                <th>{{ translate('Campaign') }}</th>
                <th>{{ translate('Message') }}</th>
                <th class="text-end">{{ translate('Action') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($runs as $run)
                @php
                    $statusClass = match ($run->status) {
                        'success' => 'bg-success',
                        'failed' => 'bg-danger',
                        'empty' => 'bg-warning text-dark',
                        'pending_approval' => 'bg-info text-dark',
                        'dispatching' => 'bg-primary',
                        default => 'bg-secondary',
                    };
                    $campaignIds = is_array($run->campaign_ids) ? $run->campaign_ids : [];
                @endphp
                <tr data-run-id="{{ $run->id }}">
                    <td class="small text-nowrap">{{ $run->started_at?->format('d M Y H:i:s') ?? '—' }}</td>
                    <td>{{ $run->rule?->name ?? '—' }}</td>
                    <td><span class="badge bg-light text-dark">{{ ucfirst($run->trigger) }}</span></td>
                    <td><span class="badge rounded-pill {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $run->status)) }}</span></td>
                    <td>{{ (int) $run->contacts_matched }}</td>
                    <td>{{ (int) $run->contacts_dispatched }}</td>
                    <td class="small text-nowrap">
                        @if($run->duration_ms !== null)
                            {{ number_format($run->duration_ms / 1000, 2) }}s
                        @else
                            —
                        @endif
                    </td>
                    <td class="small">
                        @if($campaignIds !== [])
                            #{{ implode(', #', $campaignIds) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="small text-muted" style="max-width:220px; white-space:normal; word-break:break-word;">
                        {{ $run->message ?: ($run->error ?: '—') }}
                    </td>
                    <td class="text-end text-nowrap">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary voice-cron-run-details-btn"
                                data-run-id="{{ $run->id }}">
                            {{ translate('Voice_cron_calls_made_title') }}
                        </button>
                        @if($run->isPendingApproval())
                            @can('lead_outbound_enquiry_add')
                                <button type="button"
                                        class="btn btn-sm btn--primary voice-cron-open-dispatch-modal"
                                        data-run-id="{{ $run->id }}">
                                    {{ translate('Voice_cron_make_calls') }}
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger voice-cron-reject-run"
                                        data-run-id="{{ $run->id }}">
                                    {{ translate('Voice_cron_reject_run') }}
                                </button>
                            @endcan
                        @endif
                    </td>
                </tr>
                <tr class="d-none voice-cron-run-details-row" data-run-details-for="{{ $run->id }}">
                    <td colspan="10" class="p-0 border-0 bg-light">
                        <div class="voice-cron-run-details-slot" data-run-details-slot="{{ $run->id }}"></div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if($runs->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <span class="text-muted small">{{ translate('Page') }} {{ $runs->currentPage() }} / {{ $runs->lastPage() }}</span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    @if($runs->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">{{ translate('Previous') }}</span></li>
                    @else
                        <li class="page-item">
                            <a class="page-link voice-cron-runs-page" href="#" data-page="{{ $runs->currentPage() - 1 }}">{{ translate('Previous') }}</a>
                        </li>
                    @endif
                    @if($runs->hasMorePages())
                        <li class="page-item">
                            <a class="page-link voice-cron-runs-page" href="#" data-page="{{ $runs->currentPage() + 1 }}">{{ translate('Next') }}</a>
                        </li>
                    @else
                        <li class="page-item disabled"><span class="page-link">{{ translate('Next') }}</span></li>
                    @endif
                </ul>
            </nav>
        </div>
    @endif
@endif
