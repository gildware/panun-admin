@extends('adminmodule::layouts.master')

@section('title',translate('service_list'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/select.dataTables.min.css"/>
    <style>
        #ServiceListTableContainer a.category-list-name-link:hover,
        #ServiceListTableContainer a.category-list-name-link:focus {
            color: var(--bs-dark) !important;
        }
    </style>
@endpush

@section('content')
    @php
        $listQuery = array_filter([
            'category_id' => $category_id ?? null,
            'sub_category_id' => $sub_category_id ?? null,
            'search' => $search ?: null,
        ], fn ($value) => !is_null($value) && $value !== '');
    @endphp

    <div class="filter-aside">
        <div class="filter-aside__header d-flex justify-content-between align-items-center">
            <h3 class="filter-aside__title">{{ translate('Filter') }}</h3>
            <button type="button" class="btn-close p-2 btn-close-white"></button>
        </div>
        <form action="{{ url()->current() }}?status={{ $status }}" method="POST" id="service-filter-form">
            @csrf
            @if($search)
                <input type="hidden" name="search" value="{{ $search }}">
            @endif
            <div class="filter-aside__body d-flex flex-column">
                <div class="filter-aside__category_select">
                    <h4 class="fw-normal mb-2">{{ translate('category') }}</h4>
                    <div class="mb-30">
                        <select class="category-select theme-input-style w-100" name="category_id"
                                id="service_list_category_select">
                            <option value="">{{ translate('all') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ ($category_id ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="filter-aside__category_select">
                    <h4 class="fw-normal mb-2">{{ translate('sub_category') }}</h4>
                    <div class="mb-30" id="service_list_sub_category_wrap">
                        <select class="subcategory-select theme-input-style w-100" name="sub_category_id"
                                id="service_list_sub_category_select">
                            <option value="">{{ translate('all') }}</option>
                            @foreach($subCategories as $subCategory)
                                <option value="{{ $subCategory->id }}"
                                    {{ ($sub_category_id ?? '') == $subCategory->id ? 'selected' : '' }}>
                                    {{ $subCategory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="filter-aside__bottom_btns p-20">
                <div class="d-flex justify-content-center gap-20">
                    <button class="btn btn--secondary text-capitalize" id="service-filter-reset-btn"
                            type="reset">{{ translate('Clear_all_Filter') }}</button>
                    <button class="btn btn--primary text-capitalize"
                            type="submit">{{ translate('Filter') }}</button>
                </div>
            </div>
        </form>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div
                        class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{translate('service_list')}}</h2>
                        <div>
                            @can('service_add')
                                <a href="{{route('admin.service.create')}}" class="btn btn--primary">
                                    <span class="material-icons">add</span>
                                    {{translate('add_service')}}
                                </a>
                            @endcan
                        </div>
                    </div>

                    <div
                        class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                        <ul class="nav nav--tabs">
                            <li class="nav-item">
                                <a class="nav-link {{$status=='all'?'active':''}}"
                                   href="{{ url()->current() }}?{{ http_build_query(array_merge($listQuery, ['status' => 'all'])) }}">
                                    {{translate('all')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{$status=='active'?'active':''}}"
                                   href="{{ url()->current() }}?{{ http_build_query(array_merge($listQuery, ['status' => 'active'])) }}">
                                    {{translate('active')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{$status=='inactive'?'active':''}}"
                                   href="{{ url()->current() }}?{{ http_build_query(array_merge($listQuery, ['status' => 'inactive'])) }}">
                                    {{translate('inactive')}}
                                </a>
                            </li>
                        </ul>

                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{translate('Total_Services')}}:</span>
                            <span class="title-color">{{$services->total()}}</span>
                        </div>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="all-tab-pane">
                            <div class="card">
                                <div class="card-body">
                                    <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                        <form action="{{url()->current()}}?status={{$status}}"
                                              class="search-form search-form_style-two"
                                              method="POST">
                                            @csrf
                                            @if($category_id)
                                                <input type="hidden" name="category_id" value="{{ $category_id }}">
                                            @endif
                                            @if($sub_category_id)
                                                <input type="hidden" name="sub_category_id" value="{{ $sub_category_id }}">
                                            @endif
                                            <div class="input-group search-form__input_group">
                                            <span class="search-form__icon">
                                                <span class="material-icons">search</span>
                                            </span>
                                                <input type="search" class="theme-input-style search-form__input"
                                                       value="{{$search}}" name="search"
                                                       placeholder="{{translate('search_here')}}">
                                            </div>
                                            <button type="submit"
                                                    class="btn btn--primary">{{translate('search')}}</button>
                                        </form>
                                        <button type="button" class="btn text-capitalize filter-btn border px-3">
                                            <span class="material-icons">filter_list</span> {{ translate('Filter') }}
                                            <span class="count">{{ $filterCounter ?? 0 }}</span>
                                        </button>
                                    </div>

                                    <div class="table-responsive" id="ServiceListTableContainer">
                                        <table id="example" class="table align-middle">
                                            <thead>
                                            <tr>
                                                <th>{{translate('name')}}</th>
                                                <th>{{translate('category')}}</th>
                                                <th>{{translate('sub_category')}}</th>
                                                <th>{{translate('variations')}}</th>
                                                <th>{{translate('Minimum Bidding Price')}}</th>
                                                @can('service_manage_status')
                                                    <th>{{translate('status')}}</th>
                                                @endcan
                                                @canany(['service_delete', 'service_update'])
                                                    <th>{{translate('action')}}</th>
                                                @endcan
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($services as $key=>$service)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('admin.service.detail', [$service->id]) }}"
                                                           class="category-list-name-link d-flex align-items-center gap-3 text-decoration-none demo_check title-color"
                                                           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                                            <div class="avatar avatar-sm flex-shrink-0">
                                                                <img class="avatar-img radius-5"
                                                                     src="{{ $service->thumbnail_full_path }}"
                                                                     alt="{{ $service->name }}">
                                                            </div>
                                                            <span class="fw-medium">{{ Str::limit($service->name, 50) }}</span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        @if($service->category)
                                                            {{$service->category->name}}
                                                        @else
                                                            <div class="d-flex">
                                                                <span>{{ translate('Unavailable') }}</span>
                                                                <i class="material-icons" data-bs-toggle="tooltip"
                                                                   data-bs-placement="top"
                                                                   title="{{translate('Update the service category')}}">info
                                                                </i>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($service->subCategory)
                                                            {{ $service->subCategory->name }}
                                                        @else
                                                            <div class="d-flex">
                                                                <span>{{ translate('Unavailable') }}</span>
                                                                <i class="material-icons" data-bs-toggle="tooltip"
                                                                   data-bs-placement="top"
                                                                   title="{{ translate('Update the service sub category') }}">info
                                                                </i>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td>{{ $service->variations_count }}</td>
                                                    <td>
                                                        {{with_currency_symbol($service->min_bidding_price)}}

                                                        @if($service->min_bidding_price == 0)
                                                            <i class="text-warning material-icons px-1"
                                                               data-bs-toggle="tooltip" data-bs-placement="top"
                                                               title="{{translate('Update the minimum bidding price')}}"
                                                            >warning</i>
                                                        @endif
                                                    </td>
                                                    @can('service_manage_status')
                                                        <td>
                                                            <label class="switcher" data-bs-toggle="modal"
                                                                   data-bs-target="#deactivateAlertModal">
                                                                <input class="switcher_input route-alert"
                                                                       data-route="{{route('admin.service.status-update',[$service->id])}}"
                                                                       data-message="{{translate('want_to_update_status')}}"
                                                                       type="checkbox" {{$service->is_active?'checked':''}}>
                                                                <span class="switcher_control"></span>
                                                            </label>
                                                        </td>
                                                    @endcan
                                                    @canany(['service_delete', 'service_update'])
                                                        <td>
                                                            <div class="d-flex gap-2">
                                                                @can('service_update')
                                                                    <a href="{{route('admin.service.edit',[$service->id])}}"
                                                                       class="action-btn btn--light-primary demo_check"
                                                                       style="--size: 30px"
                                                                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                                                        <span class="material-icons">edit</span>
                                                                    </a>
                                                                @endcan
                                                                @can('service_delete')
                                                                    <button type="button"
                                                                            data-id="delete-{{$service->id}}"
                                                                            data-message="{{translate('want_to_delete_this_service')}}?"
                                                                            class="action-btn btn--danger {{ env('APP_ENV')!='demo' ? 'form-alert' : 'demo_check'}}"
                                                                            style="--size: 30px">
                                                                    <span
                                                                        class="material-symbols-outlined">delete</span>
                                                                    </button>
                                                                    <form
                                                                        action="{{route('admin.service.delete',[$service->id])}}"
                                                                        method="post" id="delete-{{$service->id}}"
                                                                        class="hidden">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                    </form>
                                                                @endcan
                                                            </div>
                                                        </td>
                                                    @endcan
                                                </tr>
                                            @empty
                                                <tr class="text-center">
                                                    <td colspan="7">{{translate('no data available')}}</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        {!! $services->links() !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        "use strict"

        function initServiceListSubCategorySelect() {
            const $select = $('#service_list_sub_category_select');
            if (!$select.length) {
                return;
            }
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                placeholder: @json(translate('Select_sub_category')),
                allowClear: true,
            });
        }

        $(document).ready(function () {
            $('.js-select').select2();
            $('.category-select').select2({
                placeholder: @json(translate('select_category')),
                allowClear: true,
            });
            initServiceListSubCategorySelect();

            $('#service_list_category_select').on('change', function () {
                const id = this.value;
                const $wrap = $('#service_list_sub_category_wrap');
                const allOption = '<option value="">' + @json(translate('all')) + '</option>';

                if (!id) {
                    $wrap.html(
                        '<select class="subcategory-select theme-input-style w-100" name="sub_category_id" id="service_list_sub_category_select">' +
                        allOption +
                        '</select>'
                    );
                    initServiceListSubCategorySelect();
                    return;
                }

                $.get('{{ url('/') }}/admin/category/ajax-childes-only/' + id, function (response) {
                    $wrap.html(response.template);
                    const $select = $wrap.find('select');
                    $select
                        .removeClass('js-select')
                        .addClass('subcategory-select theme-input-style w-100')
                        .attr('id', 'service_list_sub_category_select')
                        .attr('name', 'sub_category_id');
                    $select.prepend(allOption);
                    initServiceListSubCategorySelect();
                });
            });

            $('#service-filter-reset-btn').on('click', function (e) {
                e.preventDefault();
                window.location.href = '{{ url()->current() }}?status={{ $status }}';
            });
        });
    </script>
    <script src="{{asset('assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.js"></script>
    <script src="{{asset('assets/admin-module')}}/plugins/dataTables/dataTables.select.min.js"></script>
@endpush
