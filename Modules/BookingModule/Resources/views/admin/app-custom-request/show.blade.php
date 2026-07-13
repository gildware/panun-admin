@extends('adminmodule::layouts.new-master')

@section('title', translate('App_Custom_Request_Details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('App_Custom_Request_Details') }}</h2>
                        <a href="{{ route('admin.booking.app-custom-requests.index') }}" class="btn btn--secondary">
                            {{ translate('Back') }}
                        </a>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body p-30">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Reference') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->reference_id }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Status') }}</div>
                                    <div class="fw-semibold text-capitalize">{{ $customRequest->status }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Customer_Name') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->name }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Phone_Number') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->phone }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Category') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->category_name ?: '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Submitted_at') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->created_at?->format('d M Y h:i a') }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">{{ translate('Description') }}</div>
                                    <div class="fw-semibold" style="white-space: pre-wrap;">{{ $customRequest->description ?: '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body p-30">
                            <h5 class="mb-3">{{ translate('Conversation') }}</h5>
                            @forelse($customRequest->messages as $message)
                                <div class="border rounded p-3 mb-3 {{ $message->sender_type === 'admin' ? 'bg-light' : '' }}">
                                    <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                                        <span class="fw-semibold text-capitalize">
                                            {{ $message->sender_type === 'admin' ? translate('Admin') : translate('Customer') }}
                                        </span>
                                        <span class="text-muted small">{{ $message->created_at?->format('d M Y h:i a') }}</span>
                                    </div>
                                    <div style="white-space: pre-wrap;">{{ $message->message }}</div>
                                </div>
                            @empty
                                <div class="text-muted">{{ translate('No_data_available') }}</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body p-30">
                            <h5 class="mb-3">{{ translate('Update_status_and_reply') }}</h5>
                            <form method="POST" action="{{ route('admin.booking.app-custom-requests.update', $customRequest->id) }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">{{ translate('Status') }}</label>
                                        <select name="status" class="form-control" required>
                                            @foreach(\Modules\BookingModule\Entities\AppCustomRequest::statusOptions() as $value => $label)
                                                <option value="{{ $value }}" @selected($customRequest->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ translate('Reply_to_customer') }}</label>
                                        <textarea name="admin_message" class="form-control" rows="4" placeholder="{{ translate('Write_your_reply_here') }}"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <div class="text-muted small mb-2">{{ translate('Lead') }}</div>
                            @if($customRequest->lead)
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <a href="{{ route('admin.lead.show', $customRequest->lead->id) }}" class="link-primary fw-semibold">
                                        #{{ $customRequest->lead->id }} — {{ $customRequest->lead->name ?: '—' }}
                                    </a>
                                    @if($customRequest->lead->source)
                                        <span class="badge bg-light text-dark">{{ $customRequest->lead->source->name }}</span>
                                    @endif
                                    <a href="{{ route('admin.booking.create-from-lead', $customRequest->lead->id) }}" class="btn btn-sm btn--primary">
                                        {{ translate('Create_Booking') }}
                                    </a>
                                </div>
                            @else
                                <div>—</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
