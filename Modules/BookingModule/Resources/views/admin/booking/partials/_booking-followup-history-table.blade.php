@php
    $followups = $followups ?? collect();
    $followupDelayMeta = $followupDelayMeta ?? [];
    $showActionColumn = $showActionColumn ?? true;
    $showSectionLabel = $showSectionLabel ?? true;
    $variant = $variant ?? 'full';
    $isActivityVariant = $variant === 'activity';
    $tableClass = trim('data-table booking-followup-history-table lead-followup-history-table mb-0 '
        .($isActivityVariant ? 'booking-followup-history-table--activity ' : '')
        .($tableClass ?? ''));
    $detailColspan = $isActivityVariant ? ($showActionColumn ? 6 : 5) : ($showActionColumn ? 10 : 9);
@endphp
@if($showSectionLabel)
<p class="table-section-label mb-0">{{ translate('Follow_up_History') }}</p>
@endif
@if($followups->isEmpty())
    <p class="text-muted small px-3 py-2 mb-0">{{ translate('No_follow_ups_yet') }}</p>
@else
    <div class="table-responsive">
        <table class="{{ $tableClass }}">
            <thead>
                <tr>
                    @if($isActivityVariant)
                        <th>{{ translate('Scheduled_for') }}</th>
                        <th>{{ translate('For') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Remarks') }}</th>
                        <th>{{ translate('Taken_By') }}</th>
                        @if($showActionColumn)
                            <th class="text-end booking-followups-table__action">{{ translate('Action') }}</th>
                        @endif
                    @else
                        <th>{{ translate('Scheduled_for') }}</th>
                        <th>{{ translate('Taken_on') }}</th>
                        <th>{{ translate('Delay') }}</th>
                        <th>{{ translate('Next_Follow_up_Date') }}</th>
                        <th>{{ translate('For') }}</th>
                        <th>{{ translate('Urgency') }}</th>
                        <th>{{ translate('Taken_By') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Follow_up_Taken_on') }}</th>
                        @if($showActionColumn)
                            <th class="text-end booking-followups-table__action">{{ translate('Action') }}</th>
                        @endif
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($followups as $followup)
                    @php
                        $delayMeta = $followupDelayMeta[$followup->id] ?? null;
                        $dueAt = $delayMeta['due_at'] ?? ($followup->status === 'scheduled' ? $followup->date : ($followup->due_followup_at ?? $followup->date));
                        $fuUrgency = $followup->urgency ?: 'medium';
                        $hasRecording = $followup->hasRecording() && $followup->recording_url;
                    @endphp
                    <tr class="booking-followup-row lead-followup-row {{ $loop->even ? 'booking-followup-row--alt lead-followup-row--alt' : '' }}">
                        @if($isActivityVariant)
                            <td class="text-nowrap">{{ $dueAt?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td>{{ translate(ucfirst($followup->for)) }}</td>
                            <td><span class="chip chip--{{ $followup->isRescheduled() ? 'info' : ($followup->status === 'completed' ? 'success' : ($followup->status === 'scheduled' ? 'warning' : 'danger')) }}">{{ $followup->followupStatusLabel() }}</span></td>
                            <td>{{ Str::limit(trim((string) ($followup->remarks ?: $followup->reason ?: '')), 60) ?: '—' }}</td>
                            <td>
                                @if($followup->createdBy)
                                    {{ trim($followup->createdBy->first_name . ' ' . $followup->createdBy->last_name) ?: $followup->createdBy->email }}
                                @else
                                    —
                                @endif
                            </td>
                        @else
                            <td class="text-nowrap">{{ $dueAt?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td class="text-nowrap">{{ $followup->followup_at?->format('d M Y, h:i A') ?? ($followup->isRescheduled() ? $followup->created_at?->format('d M Y, h:i A') : '—') }}</td>
                            <td class="text-nowrap">
                                @if($delayMeta && $dueAt && ! $followup->isRescheduled())
                                    @if($delayMeta['on_time'])
                                        <span class="chip chip--success">{{ translate('On_time') }}</span>
                                    @else
                                        <span class="chip chip--danger">{{ translate('Delayed_by') }} {{ $delayMeta['delay_label'] }}</span>
                                    @endif
                                @elseif($followup->isRescheduled())
                                    <span class="chip chip--info">{{ translate('Rescheduled') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $followup->next_followup_at?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td>{{ translate(ucfirst($followup->for)) }}</td>
                            <td><span class="chip chip--{{ $fuUrgency === 'high' ? 'danger' : ($fuUrgency === 'low' ? 'primary' : 'warning') }}">{{ translate(ucfirst($fuUrgency)) }}</span></td>
                            <td>
                                @if($followup->createdBy)
                                    {{ trim($followup->createdBy->first_name . ' ' . $followup->createdBy->last_name) ?: $followup->createdBy->email }}
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="chip chip--{{ $followup->isRescheduled() ? 'info' : ($followup->status === 'completed' ? 'success' : ($followup->status === 'scheduled' ? 'warning' : 'danger')) }}">{{ $followup->followupStatusLabel() }}</span></td>
                            <td class="text-nowrap">{{ $followup->contactChannelLabel() ?? '—' }}</td>
                        @endif
                        @if($showActionColumn)
                            <td class="text-end booking-followups-table__action">
                                <div class="d-inline-flex flex-wrap justify-content-end align-items-center gap-1">
                                    @if($followup->status === 'scheduled')
                                        <button type="button"
                                                class="btn btn-demo-accent btn-sm text-nowrap"
                                                data-bs-toggle="modal"
                                                data-bs-target="#takeFollowupModal"
                                                data-booking-take-followup
                                                data-followup-id="{{ $followup->id }}"
                                                data-followup-update-url="{{ route('admin.booking.followup.update', [$booking->id, $followup->id]) }}"
                                                data-followup-for="{{ $followup->for }}"
                                                data-followup-date="{{ $followup->date?->format('d M Y, h:i A') }}"
                                                data-followup-urgency="{{ $followup->urgency ?: 'medium' }}"
                                                data-followup-reason="{{ $followup->reason }}">
                                            {{ translate('Take_Follow_up') }}
                                        </button>
                                    @endif
                                    @if($hasRecording)
                                        <button type="button" class="ld-btn ld-btn-outline voice-call-details-toggle py-0 px-2" style="font-size:.6875rem;" aria-expanded="false">{{ translate('View') }}</button>
                                    @endif
                                    @if($followup->status !== 'scheduled' && ! $hasRecording)
                                        —
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                    @if($hasRecording)
                        <tr class="voice-call-details-row d-none">
                            <td colspan="{{ $detailColspan }}" class="p-0 border-0">
                                @include('bookingmodule::admin.booking.partials._booking_followup_recording_details_panel', ['followup' => $followup, 'booking' => $booking])
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
@endif
