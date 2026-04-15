
@push('css_or_js')

    <style>
        /* Loader overlay */
        .search-loader-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            backdrop-filter: blur(4px); /* blur background */
            background-color: rgba(255, 255, 255, 0.6); /* semi-transparent */
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        /* Spinner */
        .loader-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

    </style>

@endpush
<header class="header fixed-top">
    <div class="container-fluid">
        <div class="row align-items-center justify-content-between">
            <div class="col-2">
                <div class="header-toogle-menu">
                    <button class="toggle-menu-button aside-toggle border-0 bg-transparent p-0 dark-color">
                        <span class="material-icons">menu</span>
                    </button>
                </div>
            </div>
            <div class="col-10">
                <div class="header-right">
                    <ul class="nav justify-content-end align-items-center gap-3 gap-md-4">
                        @can('booking_view')
                        <li class="nav-item max-sm-m-0">
                            <a href="{{ route('admin.booking.create') }}" class="title-color bg--secondary border-0 rounded align-items-center py-2 px-2 px-md-3 d-inline-flex gap-1 text-decoration-none">
                                <span class="material-symbols-outlined" aria-hidden="true">add_circle</span>
                                <span class="d-none d-md-block">{{ translate('Add_New_Booking') }}</span>
                            </a>
                        </li>
                        @endcan
                        <li class="nav-item max-sm-m-0">
                            <button type="button" id="modalOpener" class="title-color bg--secondary border-0 rounded align-items-center py-2 px-2 px-md-3 d-flex gap-1" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                <span class="material-symbols-outlined">search</span>
                                <span class="d-none d-md-block">{{translate('Search')}}</span>
                                <span class="bg-card text-muted border rounded-3 p-1 fs-12 fw-bold lh-1 ms-1 ctrlplusk d-none d-md-block">Ctrl+K</span>
                            </button>
                        </li>
                        <li class="nav-item max-sm-m-0">
                            <div class="hs-unfold">
                                <div>
                                    @php($local = session()->has('local') ? session('local'):null)
                                    @php($lang = Modules\BusinessSettingsModule\Entities\BusinessSettings::where('key_name','system_language')->first())
                                    @if ($lang)
                                        <div class="topbar-text dropdown d-flex">
                                            <a class="topbar-link dropdown-toggle d-flex align-items-center title-color gap-1 justify-content-between lagn-drop-btn"
                                               href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-offset="0,20">
                                                @foreach ($lang['live_values'] as $data)
                                                    @if(is_null($local) && $data['default'])
                                                        @php($local = $data['code'])
                                                    @endif

                                                    @if($data['code']==$local)
                                                        @php($language = collect(LANGUAGES)->where('code', $data['code'])->first())
                                                        <span class="material-icons">language</span>
                                                        @if($language)
                                                            <span class="d-none d-md-block">{{ $language['nativeName'] }}</span>
                                                            <span class="fz-10 d-none d-md-block">({{ $data['code'] }})</span>
                                                        @else
                                                            <span class="d-none d-md-block">({{ $data['code'] }})</span>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </a>
                                            <ul class="dropdown-menu lang-menu">
                                                @foreach($lang['live_values'] as $key =>$data)
                                                    @if($data['status']==1)
                                                        @php($language = collect(LANGUAGES)->where('code', $data['code'])->first())
                                                        <li>
                                                            <a class="dropdown-item d-flex gap-2 align-items-center py-2 justify-content-between"
                                                               href="{{route('admin.lang',[$data['code']])}}">
                                                                @if($language)
                                                                    <div class="d-flex gap-2 align-items-center">
                                                                        <span class="text-capitalize">{{ $language['nativeName'] }}</span>
                                                                        <span class="fz-10">({{ $data['code'] }})</span>
                                                                    </div>
                                                                    @if($local == $data['code'])
                                                                        <span class="material-symbols-outlined text-muted">check_circle</span>
                                                                    @endif
                                                                @else
                                                                    <span class="text-capitalize">{{ $data['code'] }}</span>
                                                                @endif

                                                            </a>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </li>
                        @can('whatsapp_chat_view')
                        <li>
                            <div class="whatsapp-header-messages pe--12">
                                <a href="{{ route('admin.whatsapp.conversations.index', ['channel' => 'whatsapp', 'tab' => 'chats']) }}"
                                   class="header-icon count-btn wa-header-icon-link"
                                   data-bs-toggle="tooltip"
                                   data-bs-placement="bottom"
                                   title="{{ translate('WhatsApp') }}"
                                   aria-label="{{ translate('WhatsApp') }}">
                                    <span class="wa-header-whatsapp-icon d-inline-flex align-items-center justify-content-center" style="color: #25D366;" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.881 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                        </svg>
                                    </span>
                                    <span class="count" id="whatsapp_unread_count">0</span>
                                </a>
                            </div>
                        </li>
                        @endcan
                        <li>
                            <div class="messages pe--12">
                                <a href="{{route('admin.chat.index', ['user_type' => 'customer'])}}" class="header-icon count-btn">
                                    <span class="material-icons">sms</span>
                                    <span class="count" id="message_count">0</span>
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="user mt-n1">
                                <a href="#" class="header-icon user-icon" data-bs-toggle="dropdown">
                                    <img width="30" height="30"
                                         src="{{auth()->user()->profile_image_full_path}}"

                                         class="rounded-circle aspect-square object-fit-cover" alt="{{ translate('profile_image') }}">
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="{{route('admin.profile_update')}}"
                                       class="dropdown-item-text media gap-3 align-items-center">
                                        <div class="avatar">
                                            <img class="avatar-img rounded-circle aspect-square object-fit-cover" width="50" height="50"
                                                 src="{{auth()->user()->profile_image_full_path}}"
                                                 alt="{{ translate('profile-image') }}">
                                        </div>
                                        <div class="media-body ">
                                            <h5 class="card-title">{{ Str::limit(auth()->user()?->first_name, 20) }}</h5>
                                            <span class="card-text">{{ Str::limit(auth()->user()?->email, 20) }}</span>
                                        </div>
                                    </a>
                                    <a class="dropdown-item" href="{{route('admin.profile_update')}}">
                                        <span class="text-truncate" title="{{translate('Settings')}}">{{translate('Settings')}}</span>
                                    </a>
                                    <a class="dropdown-item admin-logout">
                                        <span class="text-truncate cursor-pointer" title="{{translate('Sign Out')}}">{{translate('Sign_Out')}}</span>
                                    </a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="modal fade removeSlideDown" id="staticBackdrop" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content modal-content__search border-0 {{env('APP_ENV') == 'demo' ? 'mt-5' : ''}}">
            <div class="d-flex flex-column gap-3">
                <div class="d-flex gap-2 align-items-center rounded bg-card py-2 px-3">
                    <form class="flex-grow-1" id="searchForm" action="{{ route('admin.search.routing') }}">
                        @csrf
                        <div class="d-flex align-items-center global-search-container">
                            <span class="material-symbols-outlined">search</span>
                            <input class="form-control flex-grow-1 border-0 search-input" id="searchInput" name="search" type="search" placeholder="Search" aria-label="Search" autofocus autocomplete="off">
                        </div>
                    </form>
                    <button class="border-0 rounded-3 px-2 py-1" type="button" data-bs-dismiss="modal">{{ translate('Esc') }}</button>
                </div>

                <div class="bg-card p-4 rounded-3 min-h-350">
                    <div class="search-result" id="searchResults">
                        <div id="searchLoaderOverlay" class="search-loader-overlay">
                            <div class="loader-spinner"></div>
                        </div>
                        <div class="text-center text-muted py-5">{{translate('It appears that you have not yet searched.')}}.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
