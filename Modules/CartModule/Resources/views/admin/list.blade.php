@extends('adminmodule::layouts.master')

@section('title', translate('Customer_Cart'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/dataTables/jquery.dataTables.min.css')}}"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/dataTables/select.dataTables.min.css')}}"/>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{translate('Customer_Cart')}}</h2>
                    </div>

                    {{-- Headline stats --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <div class="card h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <span class="material-icons fz-30 text--primary">groups</span>
                                    <div>
                                        <div class="opacity-75 fz-12">{{translate('Customers_with_items')}}</div>
                                        <h3 class="mb-0">{{ $summary['customers'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <span class="material-icons fz-30 text--primary">shopping_cart</span>
                                    <div>
                                        <div class="opacity-75 fz-12">{{translate('Total_items_in_carts')}}</div>
                                        <h3 class="mb-0">{{ $summary['items'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <span class="material-icons fz-30 text--primary">payments</span>
                                    <div>
                                        <div class="opacity-75 fz-12">{{translate('Estimated_cart_value')}}</div>
                                        <h3 class="mb-0">{{ with_currency_symbol($summary['value']) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3 fz-16">{{translate('Search_Filter')}}</div>
                            <form action="{{ url()->current() }}" method="GET">
                                <input type="hidden" name="search" value="{{ $queryParam['search'] ?? '' }}">
                                <input type="hidden" name="contact_status" value="{{ $queryParam['contact_status'] ?? 'all' }}">
                                <div class="row gy-lg-0 gy-4">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" id="from" name="from" value="{{ $queryParam['from'] ?? '' }}">
                                            <label for="from">{{translate('start_date')}}</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" id="to" name="to" value="{{ $queryParam['to'] ?? '' }}">
                                            <label for="to">{{translate('end_date')}}</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="form-floating">
                                            <input class="form-control" type="number" min="1" name="min_items" value="{{ $queryParam['min_items'] ?? '' }}">
                                            <label class="mb-2">{{translate('Min_items_in_cart')}}</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="form-floating">
                                            <select class="js-select" name="sort_by">
                                                <option value="recent" {{ ($queryParam['sort_by'] ?? '') == 'recent' ? 'selected' : '' }}>{{translate('Recently_updated')}}</option>
                                                <option value="oldest" {{ ($queryParam['sort_by'] ?? '') == 'oldest' ? 'selected' : '' }}>{{translate('Oldest_updated')}}</option>
                                                <option value="most_items" {{ ($queryParam['sort_by'] ?? '') == 'most_items' ? 'selected' : '' }}>{{translate('Most_items')}}</option>
                                                <option value="least_items" {{ ($queryParam['sort_by'] ?? '') == 'least_items' ? 'selected' : '' }}>{{translate('Least_items')}}</option>
                                                <option value="highest_value" {{ ($queryParam['sort_by'] ?? '') == 'highest_value' ? 'selected' : '' }}>{{translate('Highest_value')}}</option>
                                            </select>
                                            <label class="mb-2">{{translate('sort_by')}}</label>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                                        <a href="{{ url()->current() }}" class="btn btn--secondary btn-sm">{{translate('reset')}}</a>
                                        <button type="submit" class="btn btn--primary btn-sm">{{translate('filter')}}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                        @php $tabQuery = collect($queryParam)->except('contact_status')->toArray(); @endphp
                        <ul class="nav nav--tabs">
                            <li class="nav-item">
                                <a class="nav-link {{ $contactStatus == 'all' ? 'active' : '' }}"
                                   href="{{ url()->current() . '?' . http_build_query(array_merge($tabQuery, ['contact_status' => 'all'])) }}">
                                    {{ translate('all') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $contactStatus == 'pending' ? 'active' : '' }}"
                                   href="{{ url()->current() . '?' . http_build_query(array_merge($tabQuery, ['contact_status' => 'pending'])) }}">
                                    {{ translate('Not_contacted') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $contactStatus == 'contacted' ? 'active' : '' }}"
                                   href="{{ url()->current() . '?' . http_build_query(array_merge($tabQuery, ['contact_status' => 'contacted'])) }}">
                                    {{ translate('Contacted') }}
                                </a>
                            </li>
                        </ul>
                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{translate('Total')}}:</span>
                            <span class="title-color">{{ $carts->total() }}</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                <form action="{{ url()->current() }}" class="search-form search-form_style-two" method="GET">
                                    <div class="input-group search-form__input_group">
                                        <span class="search-form__icon">
                                            <span class="material-icons">search</span>
                                        </span>
                                        <input type="search" class="theme-input-style search-form__input"
                                               value="{{ $search }}" name="search"
                                               placeholder="{{translate('Search_by_customer_or_service')}}">
                                    </div>
                                    <input type="hidden" name="from" value="{{ $queryParam['from'] ?? '' }}">
                                    <input type="hidden" name="to" value="{{ $queryParam['to'] ?? '' }}">
                                    <input type="hidden" name="min_items" value="{{ $queryParam['min_items'] ?? '' }}">
                                    <input type="hidden" name="sort_by" value="{{ $queryParam['sort_by'] ?? '' }}">
                                    <input type="hidden" name="contact_status" value="{{ $queryParam['contact_status'] ?? 'all' }}">
                                    <button type="submit" class="btn btn--primary">{{translate('search')}}</button>
                                </form>

                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn--secondary text-capitalize dropdown-toggle"
                                                data-bs-toggle="dropdown">
                                            <span class="material-icons">file_download</span> {{translate('download')}}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                            <li>
                                                <a class="dropdown-item"
                                                   href="{{ env('APP_ENV') != 'demo' ? route('admin.customer-cart.download', $queryParam) : 'javascript:demo_mode()' }}">
                                                    {{translate('excel')}}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="example" class="table align-middle">
                                    <thead>
                                    <tr>
                                        <th>{{translate('Sl')}}</th>
                                        <th>{{translate('Customer')}}</th>
                                        <th>{{translate('Contact_Info')}}</th>
                                        <th class="text-center">{{translate('Items_in_cart')}}</th>
                                        <th class="text-center">{{translate('Total_Qty')}}</th>
                                        <th class="text-center">{{translate('Estimated_value')}}</th>
                                        <th>{{translate('Services')}}</th>
                                        <th class="text-center">{{translate('First_added')}}</th>
                                        <th class="text-center">{{translate('Last_updated')}}</th>
                                        <th class="text-center">{{translate('Contacted')}}</th>
                                        <th class="text-center">{{translate('action')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($carts as $key => $cart)
                                        <tr>
                                            <td>{{ $key + $carts->firstItem() }}</td>
                                            <td>
                                                <a href="{{ route('admin.customer-cart.detail', [$cart->customer_id]) }}" class="fw-medium">
                                                    {{ trim(($cart->customer_first_name ?? '') . ' ' . ($cart->customer_last_name ?? '')) ?: translate('Unknown_customer') }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    @if(env('APP_ENV') == 'demo')
                                                        <label class="badge badge-primary">{{translate('protected')}}</label>
                                                    @else
                                                        @if($cart->customer_phone)
                                                            <a href="tel:{{ $cart->customer_phone }}" class="fz-12 fw-medium d-flex align-items-center gap-1">
                                                                <span class="material-icons fz-14">call</span>{{ $cart->customer_phone }}
                                                            </a>
                                                        @endif
                                                        @if($cart->customer_email)
                                                            <a href="mailto:{{ $cart->customer_email }}" class="fz-12">{{ $cart->customer_email }}</a>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-primary">{{ $cart->items_count }}</span>
                                            </td>
                                            <td class="text-center">{{ $cart->total_quantity }}</td>
                                            <td class="text-center">{{ with_currency_symbol($cart->total_value) }}</td>
                                            <td>
                                                <span class="fz-12" title="{{ $cart->services_preview }}">
                                                    {{ \Illuminate\Support\Str::limit($cart->services_preview, 60) }}
                                                </span>
                                            </td>
                                            <td class="text-center fz-12">
                                                {{ $cart->first_added_at ? date('d M Y', strtotime($cart->first_added_at)) : '-' }}<br>
                                                <span class="opacity-75">{{ $cart->first_added_at ? date('h:i A', strtotime($cart->first_added_at)) : '' }}</span>
                                            </td>
                                            <td class="text-center fz-12">
                                                {{ $cart->last_added_at ? date('d M Y', strtotime($cart->last_added_at)) : '-' }}<br>
                                                <span class="opacity-75">{{ $cart->last_added_at ? date('h:i A', strtotime($cart->last_added_at)) : '' }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($cart->contacted_at)
                                                    <span class="badge badge-success">{{translate('Contacted')}}</span>
                                                    <div class="fz-12 mt-1">
                                                        <span class="d-block">{{ trim(($cart->contacted_by_first_name ?? '') . ' ' . ($cart->contacted_by_last_name ?? '')) ?: translate('Admin') }}</span>
                                                        <span class="opacity-75">{{ date('d M Y, h:i A', strtotime($cart->contacted_at)) }}</span>
                                                    </div>
                                                @else
                                                    <span class="badge badge-warning">{{translate('Not_contacted')}}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <button type="button"
                                                            class="action-btn {{ $cart->contacted_at ? 'btn--light-warning' : 'btn--light-success' }} contact-btn"
                                                            style="--size: 30px"
                                                            data-bs-toggle="modal" data-bs-target="#contactModal"
                                                            data-customer="{{ $cart->customer_id }}"
                                                            data-name="{{ trim(($cart->customer_first_name ?? '') . ' ' . ($cart->customer_last_name ?? '')) }}"
                                                            data-contacted-by="{{ $cart->contacted_by_id ?? '' }}"
                                                            data-note="{{ $cart->contact_note ?? '' }}"
                                                            title="{{ $cart->contacted_at ? translate('Update_contact') : translate('Mark_as_contacted') }}">
                                                        <span class="material-icons">how_to_reg</span>
                                                    </button>
                                                    @if($cart->contacted_at)
                                                        <button type="submit" form="unmark-{{ $cart->customer_id }}"
                                                                class="action-btn btn--light-danger" style="--size: 30px"
                                                                title="{{translate('Mark_as_not_contacted')}}">
                                                            <span class="material-icons">undo</span>
                                                        </button>
                                                    @endif
                                                    <a href="{{ route('admin.customer-cart.detail', [$cart->customer_id]) }}"
                                                       class="action-btn btn--light-primary" style="--size: 30px" title="{{translate('View_cart')}}">
                                                        <span class="material-icons">visibility</span>
                                                    </a>
                                                </div>
                                                <form action="{{ route('admin.customer-cart.unmark-contacted', [$cart->customer_id]) }}"
                                                      method="post" id="unmark-{{ $cart->customer_id }}" class="hidden">@csrf</form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center py-4">
                                                <img class="mb-3" width="80" src="{{ asset('assets/admin-module/img/media/empty-state-icon/default-image.png') }}" alt="" onerror="this.style.display='none'">
                                                <p class="opacity-75">{{translate('No_carts_found')}}</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end">
                                {!! $carts->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mark contacted modal --}}
    <div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 900px; width: 100%;">
            <form action="" method="post" id="contactForm" class="modal-content w-100">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{translate('Mark_as_contacted')}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3 fz-14">
                        {{translate('Customer')}}: <strong id="contactCustomerName"></strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-label" for="contacted_by">{{translate('Contacted_by')}}</label>
                        <select class="js-select-modal form-control" name="contacted_by" id="contacted_by">
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ auth()->id() == $employee->id ? 'selected' : '' }}>
                                    {{ trim($employee->first_name . ' ' . $employee->last_name) ?: $employee->id }}
                                    {{ auth()->id() == $employee->id ? '('.translate('me').')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" for="contact_note">{{translate('Remarks')}}</label>
                        <textarea class="form-control" name="note" id="contact_note" rows="3"
                                  placeholder="{{translate('Add_remarks_about_the_call')}}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{translate('cancel')}}</button>
                    <button type="submit" class="btn btn--primary">{{translate('save')}}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        "use strict"
        $(document).ready(function () {
            $('.js-select').select2();
        });

        const markRouteTemplate = '{{ route('admin.customer-cart.mark-contacted', ['id' => ':id']) }}';
        const defaultContactedBy = '{{ auth()->id() }}';

        $('#contactModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const customerId = button.data('customer');
            const name = button.data('name') || '{{ translate('Unknown_customer') }}';
            const contactedBy = button.data('contacted-by') || defaultContactedBy;
            const note = button.data('note') || '';

            $('#contactForm').attr('action', markRouteTemplate.replace(':id', customerId));
            $('#contactCustomerName').text(name);
            $('#contact_note').val(note);

            const select = $('#contacted_by');
            select.val(contactedBy);
            if (!select.val()) {
                select.val(defaultContactedBy);
            }
            if (select.hasClass('select2-hidden-accessible')) {
                select.trigger('change');
            }
        });

        $('#contacted_by').select2({dropdownParent: $('#contactModal')});
    </script>
@endpush
