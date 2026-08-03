@php
    $columns = $columns ?? [];
    $columnDefs = [
        'id' => translate('ID'),
        'name' => translate('Name'),
        'phone' => translate('Phone'),
        'category' => translate('Category'),
        'zone' => translate('Zone'),
        'subcategory' => translate('Sub_Category'),
        'reason' => translate('Cancellation_Reason'),
        'remarks' => translate('Remarks'),
        'handled_by' => translate('Handled_By'),
        'source' => translate('Source'),
        'received_at' => translate('Recieved_On'),
        'next_followup' => translate('Followup_On'),
        'followups' => translate('Followups'),
        'first_contact' => translate('First_contact'),
        'no_response' => translate('No_response'),
    ];
@endphp

<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <h4 class="mb-1">{{ $title ?? translate('Leads') }}</h4>
        @if(!empty($subtitle))
            <p class="text-muted fz-12 mb-3">{{ $subtitle }}</p>
        @endif
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    @foreach($columns as $col)
                        <th @if(in_array($col, ['followups', 'first_contact', 'no_response'], true)) class="text-end" @endif>
                            {{ $columnDefs[$col] ?? $col }}
                        </th>
                    @endforeach
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($columns as $col)
                            <td @if(in_array($col, ['followups', 'first_contact', 'no_response'], true)) class="text-end" @endif>
                                @switch($col)
                                    @case('id')
                                        #{{ $row['lead_id'] ?? '—' }}
                                        @break
                                    @case('followups')
                                        {{ $row['followup_count'] ?? 0 }}
                                        @break
                                    @case('first_contact')
                                        @if(($row['never_followed_up'] ?? false) === true)
                                            <span class="text-danger">{{ translate('Never') }}</span>
                                        @elseif(isset($row['hours_to_first_followup']))
                                            {{ $row['hours_to_first_followup'] }}h
                                            @if($row['first_followup_on_time'] === true)
                                                <span class="badge bg-success-subtle text-success border">{{ translate('On_time') }}</span>
                                            @elseif($row['first_followup_on_time'] === false)
                                                <span class="badge bg-warning-subtle text-warning border">{{ translate('Late') }}</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                        @break
                                    @case('next_followup')
                                        {{ $row['next_followup_at'] ?? '—' }}
                                        @break
                                    @case('reason')
                                        {{ $row['cancel_reason'] ?? '—' }}
                                        @break
                                    @case('remarks')
                                        <span class="text-wrap d-inline-block" style="max-width: 220px;">{{ $row['cancellation_remarks'] ?? '—' }}</span>
                                        @break
                                    @case('no_response')
                                        @if($row['is_no_response_cancel'] ?? false)
                                            <span class="badge bg-danger-subtle text-danger">{{ translate('Yes') }}</span>
                                        @else
                                            —
                                        @endif
                                        @break
                                    @default
                                        {{ $row[$col] ?? '—' }}
                                @endswitch
                            </td>
                        @endforeach
                        <td class="text-end">
                            <a href="{{ route('admin.lead.show', $row['lead_id']) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                {{ translate('View') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="text-center text-muted py-4">{{ translate('Data_not_available') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
