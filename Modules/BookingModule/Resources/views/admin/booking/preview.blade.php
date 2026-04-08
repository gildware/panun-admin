@extends('adminmodule::layouts.master')

@section('title', translate('Preview_Booking'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">{{ translate('Preview_Booking') }}</h2>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <a href="{{ $bookingGoBackUrl ?? route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']) }}"
                   class="btn btn-outline-secondary">
                    {{ translate('Go_back') }}
                </a>
                <a href="{{ route('admin.booking.create', !empty($data['reopen_source_booking_id'] ?? null) ? ['from_reopen' => 1] : []) }}" class="btn btn-secondary">
                    {{ translate('Back_to_Edit') }}
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                    {{-- 0. Booking Source --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Booking_Source') }}</h4>
                        <p>
                            <strong>{{ translate('Source') }}:</strong>
                            @php
                                $sourceLabelMap = [
                                    'call' => translate('Call'),
                                    'whatsapp' => translate('Whatsapp'),
                                    'social_media' => translate('Social_Media'),
                                ];
                                $sourceKey = strtolower($data['booking_source'] ?? 'whatsapp');
                            @endphp
                            {{ $sourceLabelMap[$sourceKey] ?? ucfirst($sourceKey) }}
                        </p>
                    </div>

                    {{-- 1. Customer Information --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Customer_information') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>{{ translate('Customer_Name') }}:</strong> {{ $customer->first_name ?? 'N/A' }} {{ $customer->last_name ?? '' }}</p>
                                <p><strong>{{ translate('Phone') }}:</strong> {{ $customer->phone ?? 'N/A' }}</p>
                                <p><strong>{{ translate('Email') }}:</strong> {{ $customer->email ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                @if($data['service_location'] === 'provider')
                                    <p><strong>{{ translate('Service_Location') }}:</strong> {{ translate('Provider_Location') }}</p>
                                @elseif($address)
                                    <p><strong>{{ translate('Service_Address') }}:</strong></p>
                                    <p>{{ $address->address ?? 'N/A' }}@if(!empty($address->landmark)), {{ $address->landmark }}@endif</p>
                                @elseif(!empty($data['service_location']) && $data['service_location'] !== 'customer')
                                    <p><strong>{{ translate('Service_Location') }}:</strong> {{ $data['service_location'] }}</p>
                                @else
                                    <p><strong>{{ translate('Service_Address') }}:</strong> {{ translate('Not_specified') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- 2. Service Information --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Service_information') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>{{ translate('Zone') }}:</strong> {{ $zone->name ?? 'N/A' }}</p>
                                <p><strong>{{ translate('Category') }}:</strong> {{ $category->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>{{ translate('Sub_Category') }}:</strong> {{ $subCategory->name ?? 'N/A' }}</p>
                                <p><strong>{{ translate('Service') }}:</strong> {{ $service->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        @if($variation)
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <p><strong>{{ translate('variant') }}:</strong> {{ $variation->variant ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>{{ translate('Price') }}:</strong>
                                        {{ number_format($variation->price ?? 0, 2) }} {{ translate('currency') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if(!empty($data['service_description']))
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <p><strong>{{ translate('Service_Additional_Details') }}:</strong></p>
                                    <p>{{ $data['service_description'] }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- 3. Date & Time --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Date_&_Time') }}</h4>
                        <p><strong>{{ translate('Service_Schedule') }}:</strong> {{ \Carbon\Carbon::parse($data['service_schedule'])->format('Y-m-d H:i') }}</p>
                    </div>

                    {{-- 4. Provider Information --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Provider_information') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>{{ translate('Company_Name') }}:</strong> {{ $provider->company_name ?? 'N/A' }}</p>
                                <p><strong>{{ translate('Contact_Person') }}:</strong> {{ $provider->contact_person_name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>{{ translate('Phone') }}:</strong> {{ $provider->contact_person_phone ?? ($provider->company_phone ?? 'N/A') }}</p>
                                <p><strong>{{ translate('Status') }}:</strong> <span class="badge bg-success">{{ translate('Subscribed') }}</span></p>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Assignment --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Assignment') }}</h4>
                        @if($assignee)
                            <p><strong>{{ translate('Assignee') }}:</strong>
                                {{ $assignee->first_name }} {{ $assignee->last_name }}
                                ({{ $assignee->user_type === 'super-admin' ? translate('Admin') : translate('Employee') }})
                                - {{ $assignee->email ?? $assignee->phone }}
                            </p>
                        @else
                            <p><strong>{{ translate('Assignee') }}:</strong> {{ translate('Unassigned') }}</p>
                        @endif
                    </div>

                    @if(!empty($createCartPreviewLines) && count($createCartPreviewLines) > 0)
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Booking_Summary') }}</h4>
                        <div class="table-responsive border rounded">
                            <table class="table text-nowrap align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th class="ps-3">{{ translate('Service') }}</th>
                                    <th>{{ translate('Price') }}</th>
                                    <th>{{ translate('Qty') }}</th>
                                    <th>{{ translate('Discount') }}</th>
                                    @if(!empty($createCartHasTax))
                                    <th>{{ company_default_tax_label() }}</th>
                                    @endif
                                    <th class="text-end pe-3">{{ translate('Total') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($createCartPreviewLines as $ln)
                                    <tr>
                                        <td class="ps-3 text-wrap">
                                            <div class="fw-semibold">{{ $ln['service_name'] ?? '' }}</div>
                                            <div class="small text-muted">{{ $ln['variant_label'] ?? '' }}</div>
                                        </td>
                                        <td>{{ with_currency_symbol($ln['service_cost_unit'] ?? 0) }}</td>
                                        <td>{{ $ln['quantity'] ?? 1 }}</td>
                                        <td>{{ with_currency_symbol(($ln['basic_discount'] ?? 0) + ($ln['campaign_discount'] ?? 0)) }}</td>
                                        @if(!empty($createCartHasTax))
                                        <td>{{ with_currency_symbol($ln['tax_amount'] ?? 0) }}</td>
                                        @endif
                                        <td class="text-end pe-3">{{ with_currency_symbol($ln['line_total_before_ac'] ?? 0) }}</td>
                                    </tr>
                                @endforeach
                                @foreach($createCartPreviewExtras ?? [] as $ex)
                                    <tr class="table-light">
                                        <td class="ps-3 text-wrap">
                                            <div class="fw-semibold">{{ $ex['title'] ?? '' }}</div>
                                            @if(!empty($ex['details']))
                                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($ex['details'], 80) }}</div>
                                            @endif
                                            <span class="badge bg-secondary">{{ ($ex['type'] ?? '') === 'spare_part' ? translate('Spare_Part') : translate('Service') }}</span>
                                        </td>
                                        <td>{{ with_currency_symbol($ex['price'] ?? 0) }}</td>
                                        <td>{{ $ex['quantity'] ?? 1 }}</td>
                                        <td>{{ with_currency_symbol($ex['discount'] ?? 0) }}</td>
                                        @if(!empty($createCartHasTax))
                                        <td>—</td>
                                        @endif
                                        <td class="text-end pe-3">{{ with_currency_symbol($ex['total'] ?? 0) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(isset($additionalChargeLines) && is_array($additionalChargeLines) && count($additionalChargeLines))
                            <p class="small text-muted mb-0 mt-2">
                                @foreach($additionalChargeLines as $acRow)
                                    @if((float)($acRow['amount'] ?? 0) > 0)
                                        <span class="me-3">{{ $acRow['name'] ?? translate('Additional_charges') }}: {{ with_currency_symbol($acRow['amount']) }}</span>
                                    @endif
                                @endforeach
                            </p>
                        @endif
                    </div>
                    @endif

                    {{-- 6. Payment Information --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Payment_information') }}</h4>
                        @if(isset($totalBilling) && $totalBilling > 0)
                            @if(!empty($additionalChargeLines) && is_array($additionalChargeLines))
                                @foreach($additionalChargeLines as $acLine)
                                    <p class="mb-1"><strong>{{ $acLine['name'] ?? translate('Additional_charges') }}:</strong> {{ with_currency_symbol($acLine['amount'] ?? 0) }}</p>
                                @endforeach
                            @elseif(!empty($data['extra_fee']) && (float)$data['extra_fee'] > 0)
                                <p class="mb-1"><strong>{{ translate('Additional_charges') }}:</strong> {{ with_currency_symbol($data['extra_fee']) }}</p>
                            @endif
                            <p class="mb-2"><strong>{{ translate('Total_Billing') }}:</strong> {{ with_currency_symbol($totalBilling) }}</p>
                        @endif
                        <p><strong>{{ translate('Payment_Method') }}:</strong> {{ $adminPaymentPreview['method_line'] ?? translate('Cash_After_Service') }}</p>
                        <p><strong>{{ translate('Advance_Paid_Amount') }}:</strong> {{ with_currency_symbol($data['advance_paid_amount'] ?? 0) }}</p>
                        @if(!empty($data['advance_transaction_id']))
                            <p><strong>{{ translate('Advance_Payment_Transaction_ID') }}:</strong> {{ $data['advance_transaction_id'] }}</p>
                        @endif
                        @if(isset($dueBalance) && $dueBalance > 0)
                            <p><strong>{{ translate('Due_Balance') }}:</strong> {{ with_currency_symbol($dueBalance) }}</p>
                        @endif
                        @if(!empty($commissionPreview))
                            <p class="mb-1"><strong>{{ translate('Company_commission') }}:</strong> {{ with_currency_symbol($commissionPreview['company_commission'] ?? 0) }}</p>
                            <p class="mb-1"><strong>{{ translate('Provider_commission') }}:</strong> {{ with_currency_symbol($commissionPreview['provider_commission'] ?? 0) }}</p>
                        @endif
                        <p class="text-muted mb-0"><small>{{ $adminPaymentPreview['footnote'] ?? translate('Final_payment_will_be_collected_upon_service_completion') }}</small></p>
                    </div>

                    {{-- Action buttons: sibling forms only (nested forms are invalid HTML and can submit the wrong action). --}}
                    <div class="d-flex justify-content-end gap-2 mt-1">
                        <form action="{{ route('admin.booking.create') }}" method="POST" class="d-inline">
                            @csrf
                            @include('bookingmodule::admin.booking.partials._preview-booking-hidden-fields', ['data' => $data])
                            <button type="submit" class="btn btn-secondary">
                                {{ translate('Edit_Details') }}
                            </button>
                        </form>
                        <form action="{{ route('admin.booking.store') }}" method="POST" id="confirm-booking-form" class="d-inline">
                            @csrf
                            @include('bookingmodule::admin.booking.partials._preview-booking-hidden-fields', ['data' => $data])
                            <button type="submit" class="btn btn-primary">
                                {{ translate('Confirm_&_Create_Booking') }}
                            </button>
                        </form>
                    </div>
            </div>
        </div>
    </div>
@endsection
