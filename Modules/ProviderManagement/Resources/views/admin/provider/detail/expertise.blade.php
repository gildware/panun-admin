@extends('adminmodule::layouts.master')

@section('title', translate('provider_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                @include('providermanagement::admin.provider.partials.provider-status-header', ['provider' => $provider])
            </div>

            <div class="mb-3">
                @include('providermanagement::admin.provider.partials._provider-detail-tabs', ['webPage' => $webPage])
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h3 class="mb-1">{{ translate('Expertise') }}</h3>
                            <p class="mb-0 text-muted">{{ translate('Expertise_tab_help') }}</p>
                        </div>
                        @if(count($expertiseGroups) > 0)
                            <div class="text-end">
                                <div class="fw-medium">{{ translate('Whole_subcategories') }}: {{ $expertiseAllServicesCount }}</div>
                                <div class="text-muted">{{ translate('Individual_services') }}: {{ $expertiseSelectedServicesCount }}</div>
                            </div>
                        @endif
                    </div>

                    @if(count($expertiseGroups) === 0)
                        <div class="text-center py-5 px-4 mx-auto" style="max-width: 40rem;">
                            <p class="title-color fw-semibold mb-2">{{ translate('No_subscribed_services_for_expertise') }}</p>
                            <p class="opacity-75 mb-3">{{ translate('Subscribe_a_subcategory_before_setting_expertise') }}</p>
                            <a class="btn btn--primary" href="{{ url()->current() }}?web_page=subscribed_services">{{ translate('Subscribed_Services') }}</a>
                        </div>
                    @else
                        <form action="{{ route('admin.provider.details.expertise.update', $provider->id) }}" method="post">
                            @csrf
                            <div class="mb-3" style="max-width: 28rem;">
                                <label class="form-label" for="expertise-search">{{ translate('Search') }}</label>
                                <input id="expertise-search" type="search" class="form-control" placeholder="{{ translate('Search_subcategory_or_service') }}" autocomplete="off">
                            </div>

                            @foreach($expertiseGroups as $group)
                                @php
                                    $groupHasExpertise = collect($group['subcategories'])->contains(fn ($sub) => ($sub['mode'] ?? 'none') !== 'none');
                                @endphp
                                <details class="expertise-group mb-3" data-expertise-group {{ $groupHasExpertise ? 'open' : '' }}>
                                    <summary>
                                        <span>{{ $group['category_name'] }}</span>
                                        <span class="text-muted fw-normal">{{ count($group['subcategories']) }}</span>
                                    </summary>
                                    <div class="expertise-grid">
                                        @foreach($group['subcategories'] as $sub)
                                            @php
                                                $searchBlob = mb_strtolower($group['category_name'].' '.$sub['name'].' '.collect($sub['services'])->pluck('name')->implode(' '));
                                                $mode = old('expertise.'.$sub['id'].'.mode', $sub['mode']);
                                                $oldServices = old('expertise.'.$sub['id'].'.services');
                                            @endphp
                                            <fieldset class="expertise-block" data-expertise-block data-search="{{ $searchBlob }}" @disabled(! $canEditExpertise)>
                                                <legend>{{ $sub['name'] }}</legend>
                                                <div class="custom-radio">
                                                    <input type="radio" id="expertise-{{ $sub['id'] }}-none" name="expertise[{{ $sub['id'] }}][mode]" value="none" @checked($mode === 'none')>
                                                    <label for="expertise-{{ $sub['id'] }}-none">{{ translate('Not_an_expert') }}</label>
                                                </div>
                                                <div class="custom-radio">
                                                    <input type="radio" id="expertise-{{ $sub['id'] }}-all" name="expertise[{{ $sub['id'] }}][mode]" value="all" @checked($mode === 'all')>
                                                    <label for="expertise-{{ $sub['id'] }}-all">{{ translate('All_services_in_this_subcategory') }}</label>
                                                </div>
                                                @if(count($sub['services']) > 0)
                                                    <div class="custom-radio">
                                                        <input type="radio" id="expertise-{{ $sub['id'] }}-some" name="expertise[{{ $sub['id'] }}][mode]" value="some" @checked($mode === 'some')>
                                                        <label for="expertise-{{ $sub['id'] }}-some">{{ translate('Only_some_services') }}</label>
                                                    </div>
                                                    <div class="expertise-services {{ $mode === 'some' ? '' : 'd-none' }}" data-expertise-services>
                                                        @foreach($sub['services'] as $service)
                                                            @php
                                                                $serviceChecked = is_array($oldServices)
                                                                    ? in_array($service['id'], $oldServices, true)
                                                                    : $service['selected'];
                                                                $serviceInputId = 'expertise-'.$sub['id'].'-service-'.$service['id'];
                                                            @endphp
                                                            <div class="form--check">
                                                                <input class="form-check-input" type="checkbox" id="{{ $serviceInputId }}" name="expertise[{{ $sub['id'] }}][services][]" value="{{ $service['id'] }}" @checked($serviceChecked)>
                                                                <label class="form-check-label" for="{{ $serviceInputId }}">{{ $service['name'] }}</label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <p class="expertise-empty mb-0">{{ translate('No_active_services_in_this_subcategory') }}</p>
                                                @endif
                                                @error('expertise.'.$sub['id'].'.services')
                                                    <div class="text-danger fs-12 mt-2">{{ $message }}</div>
                                                @enderror
                                                @error('expertise.'.$sub['id'].'.mode')
                                                    <div class="text-danger fs-12 mt-2">{{ $message }}</div>
                                                @enderror
                                            </fieldset>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach

                            <p class="expertise-none text-muted mb-3" id="expertise-no-match" hidden>{{ translate('No_subcategories_match_your_search') }}</p>

                            @if($canEditExpertise)
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn--primary">{{ translate('Save_expertise') }}</button>
                                </div>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css_or_js')
    <style>
        .expertise-group {
            border: 1px solid rgba(27, 42, 78, .12);
            border-radius: .75rem;
            background: #fff;
            padding: .35rem .9rem .9rem;
        }
        .expertise-group summary {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            font-weight: 700;
            color: #1b2a4e;
            padding: .7rem 0;
        }
        .expertise-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: .75rem;
        }
        .expertise-block {
            border: 1px solid rgba(27, 42, 78, .08);
            border-radius: .7rem;
            padding: .85rem .95rem .75rem;
            margin: 0;
            min-width: 0;
        }
        .expertise-block legend {
            float: none;
            width: auto;
            font-size: .95rem;
            font-weight: 700;
            color: #1b2a4e;
            padding: 0 .25rem;
            margin-bottom: .65rem;
        }
        .expertise-block .custom-radio {
            position: relative;
            margin-bottom: .7rem;
        }
        .expertise-block .custom-radio label {
            margin: 0;
            font-weight: 500;
        }
        .expertise-services {
            margin: 0 0 .35rem 2rem;
            padding-left: .75rem;
            border-left: 2px solid rgba(245, 180, 0, .7);
        }
        .expertise-services .form--check {
            margin-bottom: .4rem;
        }
        .expertise-services .form-check-label {
            cursor: pointer;
            font-weight: 500;
        }
        .expertise-empty {
            color: #64748b;
            font-size: .85rem;
        }
        .expertise-block:disabled {
            opacity: .85;
        }
    </style>
@endpush

@push('script')
    <script>
        (function () {
            document.querySelectorAll('[data-expertise-block]').forEach(function (block) {
                var services = block.querySelector('[data-expertise-services]');
                if (!services) {
                    return;
                }
                function sync() {
                    var selected = block.querySelector('input[type="radio"]:checked');
                    var show = selected && selected.value === 'some';
                    services.classList.toggle('d-none', !show);
                }
                block.querySelectorAll('input[type="radio"]').forEach(function (radio) {
                    radio.addEventListener('change', sync);
                });
                sync();
            });

            var search = document.getElementById('expertise-search');
            var empty = document.getElementById('expertise-none') || document.getElementById('expertise-no-match');
            if (!search) {
                return;
            }
            search.addEventListener('input', function () {
                var term = search.value.trim().toLowerCase();
                var visible = 0;
                document.querySelectorAll('[data-expertise-block]').forEach(function (block) {
                    var hay = (block.getAttribute('data-search') || '').toLowerCase();
                    var show = !term || hay.indexOf(term) !== -1;
                    block.hidden = !show;
                    if (show) {
                        visible++;
                    }
                });
                document.querySelectorAll('[data-expertise-group]').forEach(function (group) {
                    var any = Array.prototype.some.call(group.querySelectorAll('[data-expertise-block]'), function (block) {
                        return !block.hidden;
                    });
                    group.hidden = !any;
                    if (any && term) {
                        group.open = true;
                    }
                });
                if (empty) {
                    empty.hidden = visible !== 0;
                }
            });
        })();
    </script>
@endpush
