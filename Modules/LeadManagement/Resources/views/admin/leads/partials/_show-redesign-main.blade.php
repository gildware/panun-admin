<main class="lead-main">

    {{-- Source --}}
    <section class="panel lead-card-source">
        <div class="panel__head">
            <h2 class="panel__title"><span class="material-icons">campaign</span> {{ translate('Source') }} &amp; {{ translate('Ad_Source') }}</h2>
            <button type="button" class="panel__edit lead-card-edit-btn">{{ translate('Edit') }}</button>
        </div>
        <div class="panel__body">
            <div class="lead-card-view">
                @php
                    $adDisplay = $leadCtwaDisplay ?? ['name' => $lead->adSource?->name, 'image_url' => $lead->adSource?->imagePublicUrl(), 'view_ad_url' => null];
                @endphp
                <dl class="dl-grid dl-grid--3">
                    <div class="dl-item"><dt>{{ translate('Source') }}</dt><dd>{{ $lead->source?->name ?? '—' }}</dd></div>
                    <div class="dl-item">
                        <dt>{{ translate('Ad_Source') }}</dt>
                        <dd>
                            @if($adDisplay['image_url'] ?? null)
                                <img src="{{ $adDisplay['image_url'] }}" alt="" class="ad-thumb" loading="lazy" onerror="this.style.display='none'">
                            @endif
                            {{ $adDisplay['name'] ?? '—' }}
                            @if(!empty($adDisplay['view_ad_url']))
                                <a href="{{ $adDisplay['view_ad_url'] }}" target="_blank" rel="noopener" class="small d-block">{{ translate('View ad') }}</a>
                            @endif
                        </dd>
                    </div>
                    <div class="dl-item"><dt>{{ translate('Added_by') }}</dt><dd>{{ $addedByName ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="lead-card-edit d-none">
                <form method="POST" action="{{ route('admin.lead.update', $lead->id) }}" class="lead-card-form">
                    @csrf
                    @method('PUT')
                    @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">{{ translate('Source') }}</label>
                            <select name="source_id" class="form-select form-select-sm js-select">
                                <option value="">{{ translate('Select_Source') }}</option>
                                @foreach($sources as $source)
                                    <option value="{{ $source->id }}" {{ old('source_id', $lead->source_id) == $source->id ? 'selected' : '' }}>{{ $source->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ translate('Ad_Source') }}</label>
                            <select name="ad_source_id" class="form-select form-select-sm js-select">
                                <option value="">{{ translate('Select_Ad_Source') }}</option>
                                @foreach($adSources as $adSource)
                                    <option value="{{ $adSource->id }}" {{ old('ad_source_id', $lead->ad_source_id) == $adSource->id ? 'selected' : '' }}>{{ $adSource->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <button type="button" class="btn btn--secondary btn-sm lead-card-cancel">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary btn-sm">{{ translate('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- Type-specific: Provider --}}
    @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER)
        <section class="panel">
            <div class="panel__head">
                <h2 class="panel__title"><span class="material-icons">engineering</span> {{ translate('Provider_Lead_Information') }}</h2>
                <button type="button" class="panel__edit btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#leadProviderModal">{{ $hasProviderData ? translate('Edit') : translate('Add_Details') }}</button>
            </div>
            <div class="panel__body">
                @if($hasProviderData)
                    <dl class="dl-grid dl-grid--3">
                        <div class="dl-item"><dt>{{ translate('Name') }}</dt><dd>{{ $lead->name ?? '—' }}</dd></div>
                        @foreach($typeHistoryDisplay['basic'] as $row)
                            <div class="dl-item"><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>
                        @endforeach
                        @foreach($typeHistoryDisplay['service'] as $row)
                            <div class="dl-item"><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>
                        @endforeach
                    </dl>
                @else
                    <p class="mb-0 text-muted small">{{ translate('No_provider_information_added_yet') }}</p>
                @endif
            </div>
        </section>

        <section class="panel" id="provider-checklist-card">
            <div class="panel__head">
                <h2 class="panel__title"><span class="material-icons">checklist</span> {{ translate('Provider_Checklist') }}</h2>
                @if($providerChecklistItems->isNotEmpty())
                    <div class="d-flex align-items-center gap-1 provider-checklist-actions">
                        <button type="button" id="provider-checklist-edit-btn" class="panel__edit">{{ translate('Edit') }}</button>
                        <span class="provider-checklist-edit-only d-none">
                            <button type="button" id="provider-checklist-update-btn" class="btn btn-primary btn-sm" disabled>{{ translate('Update') }}</button>
                            <button type="button" id="provider-checklist-cancel-btn" class="btn btn--secondary btn-sm">{{ translate('Cancel') }}</button>
                        </span>
                    </div>
                @endif
            </div>
            <div class="panel__body p-0">
                @if($providerChecklistItems->isNotEmpty())
                    <div class="table-responsive">
                        <table class="data-table table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ translate('Item') }}</th>
                                    <th>{{ translate('Description') }}</th>
                                    <th class="text-center">{{ translate('Status') }}</th>
                                    <th class="text-center">{{ translate('Done') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($providerChecklistItems as $item)
                                @php $isDone = ($providerChecklistDoneMap[$item->id] ?? false); @endphp
                                <tr data-item-id="{{ $item->id }}" data-initial-done="{{ $isDone ? '1' : '0' }}" data-is-done="{{ $isDone ? '1' : '0' }}">
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->description ?? '—' }}</td>
                                    <td class="text-center provider-checklist-status">
                                        @if($isDone)
                                            <span class="chip chip--success">{{ translate('Done') }}</span>
                                        @else
                                            <span class="chip chip--primary">{{ translate('Pending') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center provider-checklist-action">
                                        <button type="button" class="btn btn-sm provider-checklist-toggle p-0 border-0 bg-transparent" disabled data-item-id="{{ $item->id }}" title="{{ translate('Edit_to_toggle') }}">
                                            <span class="material-icons provider-checklist-icon" style="font-size:20px;">{{ $isDone ? 'check_box' : 'check_box_outline_blank' }}</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="panel__body mb-0 text-muted small">{{ translate('No_checklist_items_configured') }}</p>
                @endif
            </div>
        </section>

    {{-- Type-specific: Customer --}}
    @elseif($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER)
        <section class="panel">
            <div class="panel__head">
                <h2 class="panel__title"><span class="material-icons">home_repair_service</span> {{ translate('Customer_Lead_Information') }}</h2>
                <button type="button" class="panel__edit btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#leadCustomerModal">{{ $hasCustomerData ? translate('Edit') : translate('Add_Details') }}</button>
            </div>
            <div class="panel__body">
                @if($hasCustomerData)
                    <dl class="dl-grid dl-grid--3">
                        @foreach(isset($typeHistoryDisplay['rows']) ? $typeHistoryDisplay['rows'] : $typeHistoryDisplay as $row)
                            @if(is_array($row) && isset($row['label']))
                                <div class="dl-item"><dt>{{ $row['label'] }}</dt><dd>@if(!empty($row['raw'])){!! $row['value'] !!}@else{{ $row['value'] }}@endif</dd></div>
                            @endif
                        @endforeach
                    </dl>
                @else
                    <p class="mb-0 text-muted small">{{ translate('No_customer_information_added_yet') }}</p>
                @endif
            </div>
        </section>

    {{-- Other types with history --}}
    @elseif($typeHistory && !empty($typeHistoryDisplay))
        @php
            $typeCardTitles = [
                \Modules\LeadManagement\Entities\Lead::TYPE_INVALID => translate('Invalid_Lead_Information'),
                \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_CUSTOMER => translate('Future_Customer_Information'),
                \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER => translate('Provider_Lead_Information'),
            ];
            $typeCardTitle = $typeCardTitles[$lead->lead_type] ?? translate('Lead_Type_Information');
        @endphp
        <section class="panel">
            <div class="panel__head">
                <h2 class="panel__title"><span class="material-icons">info</span> {{ $typeCardTitle }}</h2>
            </div>
            <div class="panel__body">
                <dl class="dl-grid dl-grid--3">
                    @foreach($typeHistoryDisplay as $row)
                        @if(is_array($row) && isset($row['label']))
                            <div class="dl-item"><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>
                        @endif
                    @endforeach
                </dl>
            </div>
        </section>
    @endif

    {{-- Future customer outbound --}}
    @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_CUSTOMER)
        <section class="panel" id="lead-outbound-enquiries-card">
            <div class="panel__head">
                <h2 class="panel__title"><span class="material-icons">outbound</span> {{ translate('Outbound_Enquiries') }}</h2>
                @can('lead_outbound_enquiry_add')
                    <button type="button" class="panel__edit btn btn--primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOutboundEnquiryModal">
                        <span class="material-icons" style="font-size:16px;">add</span> {{ translate('Add_Outbound_Enquiry') }}
                    </button>
                @endcan
            </div>
            <div class="panel__body p-0">
                @if($lead->outboundEnquiries->isEmpty())
                    <p class="px-3 py-2 mb-0 text-muted small">{{ translate('No_outbound_enquiries_yet') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="data-table table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ translate('Contacted_Through') }}</th>
                                    <th>{{ translate('Status') }}</th>
                                    <th>{{ translate('Link_Lead') }}</th>
                                    <th>{{ translate('Booking_ID') }}</th>
                                    <th>{{ translate('Date_Time') }}</th>
                                    <th>{{ translate('Handled_By') }}</th>
                                    <th>{{ translate('Remarks') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($lead->outboundEnquiries as $enquiry)
                                @php
                                    $employee = $enquiry->handledBy ?: $enquiry->createdBy;
                                    $employeeName = $employee ? (trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) ?: $employee->email) : '—';
                                    $statusName = $enquiry->statusConfig?->name ?? $enquiry->status ?? '—';
                                @endphp
                                <tr>
                                    <td class="text-capitalize">{{ $enquiry->contacted_through }}</td>
                                    <td>{{ $statusName }}</td>
                                    <td>
                                        @if($enquiry->relatedLead)
                                            <a href="{{ route('admin.lead.show', $enquiry->relatedLead->id) }}">#{{ $enquiry->relatedLead->id }}</a>
                                        @else — @endif
                                    </td>
                                    <td>
                                        @if($enquiry->booking)
                                            <a href="{{ route('admin.booking.details', $enquiry->booking->id) }}" @if(!empty($inModal)) target="_top" @endif>{{ $enquiry->booking->readable_id ?: $enquiry->booking->id }}</a>
                                        @else — @endif
                                    </td>
                                    <td>{{ $enquiry->contacted_at ? $enquiry->contacted_at->format('d M Y, h:i A') : '—' }}</td>
                                    <td>{{ $employeeName }}</td>
                                    <td>{{ $enquiry->remarks ?: '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Remarks --}}
    <section class="panel lead-card-remarks">
        <div class="panel__head">
            <h2 class="panel__title"><span class="material-icons">notes</span> {{ translate('Initial_Remarks') }}</h2>
            <button type="button" class="panel__edit lead-card-edit-btn">{{ translate('Edit') }}</button>
        </div>
        <div class="panel__body">
            <div class="lead-card-view">
                <p class="remarks-text mb-0">{{ $lead->remarks ?: '—' }}</p>
            </div>
            <div class="lead-card-edit d-none">
                <form method="POST" action="{{ route('admin.lead.update', $lead->id) }}" class="lead-card-form">
                    @csrf
                    @method('PUT')
                    @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                    <textarea name="remarks" class="form-control form-control-sm" rows="3" placeholder="{{ translate('Remarks') }}">{{ old('remarks', $lead->remarks) }}</textarea>
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <button type="button" class="btn btn--secondary btn-sm lead-card-cancel">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary btn-sm">{{ translate('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

</main>
