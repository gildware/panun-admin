@extends('adminmodule::layouts.master')

@section('title', translate('Booking_created_successfully'))

@section('content')
    <div class="content container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-body text-center p-5">
                        {{-- Success Icon --}}
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width: 100px; height: 100px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="#28a745" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 2.061 4.903a.75.75 0 0 0-1.06 1.061l5.523 5.523a.75.75 0 0 0 1.07-.01l5.523-5.523a.75.75 0 0 0-.022-1.08z"/>
                                </svg>
                            </div>
                        </div>

                        {{-- Success Message --}}
                        <h2 class="mb-3 text-success">{{ translate('Booking_created_successfully') }}!</h2>
                        <p class="text-muted mb-4">
                            {{ translate('Your_booking_has_been_created_successfully') }}.
                            <br>
                            <strong>{{ translate('Booking') }}# {{ $booking->readable_id ?? 'N/A' }}</strong>
                        </p>

                        {{-- Action Buttons --}}
                        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mt-4">
                            {{-- Add New Booking --}}
                            <a href="{{ route('admin.booking.create') }}" class="btn btn-primary">
                                {{ translate('Add_New_Booking') }}
                            </a>

                            {{-- View Booking Details --}}
                            <a href="{{ route('admin.booking.details', ['id' => $booking->id]) }}?web_page=details" class="btn btn-outline-primary">
                                {{ translate('View_Details') }}
                            </a>

                            {{-- Go to Dashboard --}}
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                                {{ translate('dashboard') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
