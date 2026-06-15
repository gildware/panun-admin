@extends('adminmodule::layouts.master')

@section('title', translate('Customer_Cart_Details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{translate('Customer_Cart_Details')}}</h2>
                        <a href="{{ route('admin.customer-cart.index') }}" class="btn btn--secondary">
                            <span class="material-icons">arrow_back</span> {{translate('back')}}
                        </a>
                    </div>

                    @php
                        $totalValue = $items->sum('total_cost');
                        $totalQty = $items->sum('quantity');
                    @endphp

                    {{-- Customer summary --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-lg-5">
                                    <h5 class="mb-1">{{ trim($customer->first_name . ' ' . $customer->last_name) ?: translate('Unknown_customer') }}</h5>
                                    <div class="d-flex flex-column gap-1">
                                        @if(env('APP_ENV') == 'demo')
                                            <label class="badge badge-primary w-fit">{{translate('protected')}}</label>
                                        @else
                                            @if($customer->phone)
                                                <a href="tel:{{ $customer->phone }}" class="fz-13 d-flex align-items-center gap-1">
                                                    <span class="material-icons fz-16">call</span>{{ $customer->phone }}
                                                </a>
                                            @endif
                                            @if($customer->email)
                                                <a href="mailto:{{ $customer->email }}" class="fz-13 d-flex align-items-center gap-1">
                                                    <span class="material-icons fz-16">mail</span>{{ $customer->email }}
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="row g-2 text-center">
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <div class="opacity-75 fz-12">{{translate('Items_in_cart')}}</div>
                                                <h4 class="mb-0">{{ $items->count() }}</h4>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <div class="opacity-75 fz-12">{{translate('Total_Qty')}}</div>
                                                <h4 class="mb-0">{{ $totalQty }}</h4>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <div class="opacity-75 fz-12">{{translate('Estimated_value')}}</div>
                                                <h4 class="mb-0">{{ with_currency_symbol($totalValue) }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Contact status --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fz-16 mb-1">{{translate('Contact_status')}}</div>
                                    @if($contact && $contact->contacted_at)
                                        <span class="badge badge-success">{{translate('Contacted')}}</span>
                                        <div class="fz-13 mt-2">
                                            <span class="d-block">
                                                <span class="opacity-75">{{translate('Contacted_by')}}:</span>
                                                {{ $contact->contactedBy ? trim($contact->contactedBy->first_name . ' ' . $contact->contactedBy->last_name) : translate('Admin') }}
                                            </span>
                                            <span class="d-block">
                                                <span class="opacity-75">{{translate('Contacted_at')}}:</span>
                                                {{ date('d M Y, h:i A', strtotime($contact->contacted_at)) }}
                                            </span>
                                            @if($contact->note)
                                                <span class="d-block">
                                                    <span class="opacity-75">{{translate('Note')}}:</span> {{ $contact->note }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge badge-warning">{{translate('Not_contacted')}}</span>
                                    @endif
                                </div>
                            </div>

                            <form action="{{ route('admin.customer-cart.mark-contacted', [$customer->id]) }}" method="post" class="mt-3">
                                @csrf
                                <div class="row g-3 align-items-start">
                                    <div class="col-md-4">
                                        <label class="form-label" for="contacted_by">{{translate('Contacted_by')}}</label>
                                        <select class="form-control" name="contacted_by" id="contacted_by">
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee->id }}"
                                                    {{ ($contact->contacted_by ?? auth()->id()) == $employee->id ? 'selected' : '' }}>
                                                    {{ trim($employee->first_name . ' ' . $employee->last_name) ?: $employee->id }}
                                                    {{ auth()->id() == $employee->id ? '('.translate('me').')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label" for="note">{{translate('Remarks')}}</label>
                                        <textarea class="form-control" id="note" name="note" rows="2"
                                                  placeholder="{{translate('Add_remarks_about_the_call')}}">{{ $contact->note ?? '' }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn--primary">
                                            <span class="material-icons">how_to_reg</span>
                                            {{ $contact && $contact->contacted_at ? translate('Update_contact') : translate('Mark_as_contacted') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                            @if($contact && $contact->contacted_at)
                                <form action="{{ route('admin.customer-cart.unmark-contacted', [$customer->id]) }}" method="post" class="mt-2">
                                    @csrf
                                    <button type="submit" class="btn btn--secondary btn-sm">
                                        <span class="material-icons">undo</span> {{translate('Mark_as_not_contacted')}}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Cart items --}}
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3 fz-16">{{translate('Cart_Items')}}</div>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                    <tr>
                                        <th>{{translate('Sl')}}</th>
                                        <th>{{translate('Service')}}</th>
                                        <th>{{translate('Category')}}</th>
                                        <th class="text-center">{{translate('Qty')}}</th>
                                        <th class="text-center">{{translate('Estimated_value')}}</th>
                                        <th>{{translate('Preferred_schedule')}}</th>
                                        <th class="text-center">{{translate('Added_on')}}</th>
                                        <th class="text-center">{{translate('Last_updated')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($items as $key => $item)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $item->service->name ?? translate('Service_unavailable') }}</td>
                                            <td>
                                                {{ $item->category->name ?? '-' }}
                                                @if($item->sub_category)
                                                    <span class="opacity-75 fz-12">/ {{ $item->sub_category->name }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $item->quantity }}</td>
                                            <td class="text-center">{{ with_currency_symbol($item->total_cost) }}</td>
                                            <td class="fz-12">
                                                {{ $item->service_schedule ? date('d M Y, h:i A', strtotime($item->service_schedule)) : translate('Not_set') }}
                                            </td>
                                            <td class="text-center fz-12">
                                                {{ date('d M Y', strtotime($item->created_at)) }}<br>
                                                <span class="opacity-75">{{ date('h:i A', strtotime($item->created_at)) }}</span>
                                            </td>
                                            <td class="text-center fz-12">
                                                {{ date('d M Y', strtotime($item->updated_at)) }}<br>
                                                <span class="opacity-75">{{ date('h:i A', strtotime($item->updated_at)) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 opacity-75">{{translate('No_items_in_this_cart')}}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
