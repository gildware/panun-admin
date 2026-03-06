@extends('adminmodule::layouts.master')

@section('title', translate('Preview_Booking'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">{{ translate('Preview_Booking') }}</h2>
            <a href="{{ route('admin.booking.create') }}" class="btn btn-secondary">
                {{ translate('Back_to_Edit') }}
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.booking.store') }}" method="POST" id="confirm-booking-form">
                    @csrf
                    
                    {{-- Hidden fields with all data --}}
                    @foreach($data as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

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
                                    <p>{{ $address->address ?? 'N/A' }}, {{ $address->city ?? '' }}, {{ $address->zip_code ?? '' }}</p>
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

                    {{-- 5. Payment Information --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Payment_information') }}</h4>
                        <p><strong>{{ translate('Payment_Method') }}:</strong> {{ translate('Cash_After_Service') }}</p>
                        <p><strong>{{ translate('Advance_Paid_Amount') }}:</strong> {{ number_format($data['advance_paid_amount'] ?? 0, 2) }} {{ translate('currency') }}</p>
                        <p class="text-muted"><small>{{ translate('Final_payment_will_be_collected_upon_service_completion') }}</small></p>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-end gap-2">
                        <form action="{{ route('admin.booking.create') }}" method="GET" style="display: inline;">
                            @foreach($data as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <button type="submit" class="btn btn-secondary">
                                {{ translate('Edit_Details') }}
                            </button>
                        </form>
                        <button type="submit" form="confirm-booking-form" class="btn btn-primary">
                            {{ translate('Confirm_&_Create_Booking') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            // Handle form submission
            $('#confirm-booking-form').on('submit', function(e) {
                // Form will submit normally
            });
        });
    </script>
@endpush
