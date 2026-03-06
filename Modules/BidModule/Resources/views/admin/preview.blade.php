@extends('adminmodule::layouts.master')

@section('title', translate('Preview_Custom_Service_Request'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">{{ translate('Preview_Custom_Service_Request') }}</h2>
            <a href="{{ route('admin.booking.post.create') }}" class="btn btn-secondary">
                {{ translate('Back_to_Edit') }}
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.booking.post.store') }}" method="POST" id="confirm-bidding-form">
                    @csrf

                    {{-- Hidden fields --}}
                    <input type="hidden" name="customer_id" value="{{ $data['customer_id'] ?? '' }}">
                    <input type="hidden" name="service_address_id" value="{{ $data['service_address_id'] ?? '' }}">
                    <input type="hidden" name="zone_id" value="{{ $data['zone_id'] ?? '' }}">
                    <input type="hidden" name="category_id" value="{{ $data['category_id'] ?? '' }}">
                    <input type="hidden" name="sub_category_id" value="{{ $data['sub_category_id'] ?? '' }}">
                    @if(!empty($data['service_id']))
                        <input type="hidden" name="service_id" value="{{ $data['service_id'] }}">
                    @endif
                    <input type="hidden" name="service_description" value="{{ $data['service_description'] ?? '' }}">
                    <input type="hidden" name="booking_schedule" value="{{ $data['booking_schedule'] ?? '' }}">
                    <input type="hidden" name="booking_source" value="{{ $data['booking_source'] ?? 'admin_panel' }}">
                    @if(!empty($data['assignee_id']))
                        <input type="hidden" name="assignee_id" value="{{ $data['assignee_id'] }}">
                    @endif
                    @if(!empty($data['additional_instructions']))
                        @foreach(array_filter($data['additional_instructions']) as $instruction)
                            <input type="hidden" name="additional_instructions[]" value="{{ $instruction }}">
                        @endforeach
                    @endif

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
                                @if($address)
                                    <p><strong>{{ translate('Service_Address') }}:</strong></p>
                                    <p>{{ $address->address ?? ($address->street ?? 'N/A') }}, {{ $address->city ?? '' }}, {{ $address->zip_code ?? '' }}</p>
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
                                <p><strong>{{ translate('Sub_Category') }}:</strong> {{ $subCategory->name ?? 'N/A' }}</p>
                                <p><strong>{{ translate('Service') }}:</strong> {{ $service?->name ?? translate('Custom_Service') }}</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <p><strong>{{ translate('Custom_Service_Description') }}:</strong></p>
                                <p>{{ $data['service_description'] ?? '' }}</p>
                            </div>
                        </div>
                        @if(!empty($data['additional_instructions']) && count(array_filter($data['additional_instructions'])) > 0)
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <p><strong>{{ translate('Additional_Instructions') }}:</strong></p>
                                    <ul class="mb-0">
                                        @foreach(array_filter($data['additional_instructions']) as $instruction)
                                            <li>{{ $instruction }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- 3. Date & Time --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Date_&_Time') }}</h4>
                        <p><strong>{{ translate('Service_Schedule') }}:</strong> {{ \Carbon\Carbon::parse($data['booking_schedule'] ?? '')->format('Y-m-d H:i') }}</p>
                    </div>

                    {{-- 4. Source --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Source') }}</h4>
                        @php
                            $sourceLabelMap = [
                                'call' => translate('Call'),
                                'whatsapp' => translate('Whatsapp'),
                                'social_media' => translate('Social_Media'),
                            ];
                            $sourceKey = strtolower($data['booking_source'] ?? 'whatsapp');
                        @endphp
                        <p><strong>{{ translate('Booking_Source') }}:</strong> {{ $sourceLabelMap[$sourceKey] ?? ucfirst($sourceKey) }}</p>
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

                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-end gap-2">
                        <form action="{{ route('admin.booking.post.create') }}" method="GET" style="display: inline;">
                            <input type="hidden" name="customer_id" value="{{ $data['customer_id'] ?? '' }}">
                            <input type="hidden" name="service_address_id" value="{{ $data['service_address_id'] ?? '' }}">
                            <input type="hidden" name="zone_id" value="{{ $data['zone_id'] ?? '' }}">
                            <input type="hidden" name="category_id" value="{{ $data['category_id'] ?? '' }}">
                            <input type="hidden" name="sub_category_id" value="{{ $data['sub_category_id'] ?? '' }}">
                            @if(!empty($data['service_id']))<input type="hidden" name="service_id" value="{{ $data['service_id'] }}">@endif
                            <input type="hidden" name="service_description" value="{{ $data['service_description'] ?? '' }}">
                            <input type="hidden" name="booking_schedule" value="{{ $data['booking_schedule'] ?? '' }}">
                            <input type="hidden" name="booking_source" value="{{ $data['booking_source'] ?? 'admin_panel' }}">
                            @if(!empty($data['assignee_id']))<input type="hidden" name="assignee_id" value="{{ $data['assignee_id'] }}">@endif
                            @if(!empty($data['additional_instructions']))
                                @foreach(array_filter($data['additional_instructions']) as $instruction)
                                    <input type="hidden" name="additional_instructions[]" value="{{ $instruction }}">
                                @endforeach
                            @endif
                            <button type="submit" class="btn btn-secondary">{{ translate('Edit_Details') }}</button>
                        </form>
                        <button type="submit" form="confirm-bidding-form" class="btn btn-primary">
                            {{ translate('Confirm_&_Create_Custom_Service_Request') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
