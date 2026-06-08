@if($listError)
    <div class="alert alert-danger mb-0">{{ translate($listError) }}</div>
@else
    @php
        $leadTypes = \Modules\LeadManagement\Entities\Lead::leadTypes();
        $waPrepareUrl = route('admin.whatsapp.conversations.prepare-open', ['channel' => 'whatsapp']);
        $colspan = 10;
    @endphp
    <div class="card">
        <div class="card-body p-30">
            @if($paginator)
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <p class="text-muted small mb-0">
                        {{ translate('Total') }}: {{ $paginator->total() }}
                        · {{ translate('WhatsApp_followup_candidates') }}
                    </p>
                </div>
            @endif

            <div class="table-responsive wa-followup-table-wrap">
                <table class="table table-hover align-middle wa-followup-table" id="wa-followup-table">
                    <thead>
                    <tr>
                        <th style="width:36px">
                            <input type="checkbox" class="form-check-input" id="wa-followup-select-all" aria-label="{{ translate('Select') }}">
                        </th>
                        <th>{{ translate('Name') }}</th>
                        <th>{{ translate('Phone_Number') }}</th>
                        <th>{{ translate('Not_replied_since') }}</th>
                        <th>{{ translate('Lead_type') }}</th>
                        <th>{{ translate('Lead_Status') }}</th>
                        <th>{{ translate('whatsapp_chat_tags_label') }}</th>
                        <th>{{ translate('WhatsApp') }} {{ translate('Status') }}</th>
                        <th>{{ translate('Last_followup_call_on') }}</th>
                        <th class="text-center">{{ translate('Action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($candidates as $candidate)
                        @php
                            $type = (string) ($candidate['lead_type'] ?? '');
                            $typeLabel = $leadTypes[$type] ?? ($type !== '' ? ucfirst($type) : translate('Unknown'));
                            $typeBadge = match ($type) {
                                \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER => 'bg-success',
                                \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER => 'bg-primary',
                                \Modules\LeadManagement\Entities\Lead::TYPE_UNKNOWN => 'bg-warning text-dark',
                                default => 'bg-secondary',
                            };
                            $chatStatus = is_array($candidate['chat_status'] ?? null) ? $candidate['chat_status'] : null;
                            $waBucket = (string) ($chatStatus['bucket'] ?? 'open');
                            $waBucketOpen = $waBucket !== 'closed';
                            $hasSummary = !empty($candidate['cached_summary']);
                            $summaryText = $hasSummary ? (string) $candidate['cached_summary'] : '';
                            $summaryActionTitle = $hasSummary ? translate('Regenerate_summary') : translate('Generate_summary');
                        @endphp
                        <tr data-phone="{{ $candidate['phone'] }}">
                            <td>
                                <input type="checkbox" class="form-check-input wa-followup-row-check" value="{{ $candidate['phone'] }}">
                            </td>
                            <td class="wa-followup-contact-name">{{ $candidate['display_name'] ?? '—' }}</td>
                            <td>{{ $candidate['phone'] ?? '—' }}</td>
                            <td>
                                <span>{{ $candidate['silent_since_label'] ?? '—' }}</span>
                                @if(!empty($candidate['silent_duration_label']))
                                    <span class="text-muted ms-1">({{ $candidate['silent_duration_label'] }})</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill {{ $typeBadge }} text-capitalize">{{ $typeLabel }}</span>
                            </td>
                            <td>
                                <span class="badge rounded-pill {{ $candidate['lead_status_badge'] ?? 'bg-secondary' }}">
                                    {{ translate($candidate['lead_status_label'] ?? 'Closed') }}
                                </span>
                            </td>
                            <td>
                                @if(!empty($candidate['chat_tags']))
                                    <span class="wa-followup-tags">
                                        @foreach($candidate['chat_tags'] as $tag)
                                            <span class="badge" style="background-color:{{ $tag['color'] ?: '#6c757d' }};color:#fff;">{{ $tag['name'] }}</span>
                                        @endforeach
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill {{ $waBucketOpen ? 'bg-info text-dark' : 'bg-secondary' }}">
                                    {{ $waBucketOpen ? translate('Open') : translate('Closed') }}
                                </span>
                            </td>
                            <td class="text-muted">
                                {{ $candidate['last_followup_at_label'] ?? '—' }}
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex flex-nowrap gap-1 justify-content-center">
                                    <button type="button"
                                            class="btn btn-sm btn--secondary wa-followup-summary-toggle"
                                            data-phone="{{ $candidate['phone'] }}"
                                            aria-expanded="false">
                                        {{ translate('Summary') }}
                                    </button>
                                    @can('whatsapp_chat_view')
                                        <button type="button"
                                                class="btn btn-sm btn--primary wa-followup-open-whatsapp"
                                                data-phone="{{ $candidate['phone'] }}"
                                                data-prepare-url="{{ $waPrepareUrl }}">
                                            {{ translate('Conversation') }}
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        <tr class="wa-followup-details-row voice-call-details-row d-none" data-phone="{{ $candidate['phone'] }}">
                            <td colspan="{{ $colspan }}" class="p-0 border-0">
                                <div class="voice-call-details-panel p-2 px-3">
                                    <div class="wa-followup-call-context-cell" data-phone="{{ $candidate['phone'] }}">
                                        @include('leadmanagement::admin.voice-calls._whatsapp_followup_call_context', [
                                            'phone' => $candidate['phone'],
                                            'callContext' => $candidate['call_context'] ?? [],
                                            'callReasonLabels' => $callReasonLabels ?? [],
                                            'contextKeys' => $contextKeys ?? [],
                                            'needsRefresh' => !empty($candidate['cached_summary_needs_refresh']),
                                            'summaryActionTitle' => $summaryActionTitle,
                                        ])
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $colspan }}" class="text-center text-muted py-4">{{ translate('no_data_found') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($paginator && $paginator->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <span class="text-muted small">
                        {{ translate('Page') }} {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
                    </span>
                    <nav>
                        <ul class="pagination mb-0">
                            @if($paginator->onFirstPage())
                                <li class="page-item disabled"><span class="page-link">{{ translate('Previous') }}</span></li>
                            @else
                                <li class="page-item">
                                    <a class="page-link wa-followup-page-link" href="#" data-page="{{ $paginator->currentPage() - 1 }}">{{ translate('Previous') }}</a>
                                </li>
                            @endif
                            @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $p => $url)
                                <li class="page-item {{ $p === $paginator->currentPage() ? 'active' : '' }}">
                                    <a class="page-link wa-followup-page-link" href="#" data-page="{{ $p }}">{{ $p }}</a>
                                </li>
                            @endforeach
                            @if($paginator->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link wa-followup-page-link" href="#" data-page="{{ $paginator->currentPage() + 1 }}">{{ translate('Next') }}</a>
                                </li>
                            @else
                                <li class="page-item disabled"><span class="page-link">{{ translate('Next') }}</span></li>
                            @endif
                        </ul>
                    </nav>
                </div>
            @endif
        </div>
    </div>
@endif
