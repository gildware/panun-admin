@extends('adminmodule::layouts.new-master')

@section('title',translate('zone_setup'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/dataTables/jquery.dataTables.min.css')}}"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/dataTables/select.dataTables.min.css')}}"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module/css/zone-module.css')}}"/>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('zone_setup')}}</h2>
                    </div>

                    @can('zone_add')
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn--primary" id="add-zone-form-btn">
                                {{translate('add_new')}} {{translate('zone')}}
                            </button>
                        </div>
                    @endcan

                    @can('zone_add')
                        <div class="card zone-setup-instructions mb-30 d-none" id="zone-form-wrapper">
                            <div class="card-body p-30">
                                <form id="zone-form" action="{{route('admin.zone.store')}}"
                                      enctype="multipart/form-data"
                                      method="POST">
                                    @csrf
                                    <div class="row justify-content-between">
                                        <div class="col-lg-5 col-xl-4 mb-5 mb-lg-0">
                                            <h4 class="mb-3 c1">{{translate('instructions')}}</h4>
                                            <div class="d-flex flex-column">
                                                <p>{{translate('create_zone_by_click_on_map_and_connect_the_dots_together')}}</p>

                                                <div class="media mb-2 gap-3 align-items-center">
                                                    <img
                                                        src="{{asset('assets/admin-module/img/icons/map-drag.png')}}"
                                                        alt="{{ translate('image') }}" class="map-icon-global">
                                                    <div class="media-body ">
                                                        <p>{{translate('use_this_to_drag_map_to_find_proper_area')}}</p>
                                                    </div>
                                                </div>

                                                <div class="media gap-3 align-items-center">
                                                    <img
                                                        src="{{asset('assets/admin-module/img/icons/map-draw.png')}}"
                                                        alt="{{ translate('image') }}" class="map-icon-global">
                                                    <div class="media-body ">
                                                        <p>{{translate('click_this_icon_to_start_pin_points_in_the_map_and_connect_them_to_draw_a_
                                                        zone_._Minimum_3_points_required')}}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="map-img mt-4">
                                                    <img class="dark-support"
                                                         src="{{asset('assets/admin-module/img/instructions.gif')}}"
                                                         alt="{{ translate('image') }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-7">
                                            @php
                                                $language = \Modules\BusinessSettingsModule\Entities\BusinessSettings::where('key_name', 'system_language')->first();
                                            @endphp
                                            @if($language)
                                                <ul class="nav nav--tabs border-color-primary mb-4">
                                                    <li class="nav-item">
                                                        <a class="nav-link lang_link active"
                                                           href="#"
                                                           id="default-link">{{translate('default')}}</a>
                                                    </li>
                                                    @foreach ($language?->live_values as $lang)
                                                        <li class="nav-item">
                                                            <a class="nav-link lang_link"
                                                               href="#"
                                                               id="{{ $lang['code'] }}-link">{{ get_language_name($lang['code']) }}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                            @if($language)
                                                <div class="form-floating form-floating__icon mb-30 lang-form"
                                                     id="default-form">
                                                    <input type="text" name="name[]" class="form-control"
                                                           placeholder="{{translate('zone_name')}}" required>
                                                    <label>{{translate('zone_name')}} ({{ translate('default') }}
                                                        )</label>
                                                    <span class="material-icons">note_alt</span>
                                                </div>
                                                <input type="hidden" name="lang[]" value="default">
                                                @foreach ($language?->live_values as $lang)
                                                    <div
                                                        class="form-floating form-floating__icon mb-30 d-none lang-form"
                                                        id="{{$lang['code']}}-form">
                                                        <input type="text" name="name[]" class="form-control"
                                                               placeholder="{{translate('zone_name')}}">
                                                        <label>{{translate('zone_name')}}
                                                            ({{strtoupper($lang['code'])}})</label>
                                                        <span class="material-icons">note_alt</span>
                                                    </div>
                                                    <input type="hidden" name="lang[]" value="{{$lang['code']}}">
                                                @endforeach
                                            @else
                                                <div class="lang-form">
                                                    <div class="mb-30">
                                                        <div class="form-floating form-floating__icon">
                                                            <input type="text" class="form-control" name="name[]"
                                                                   placeholder="{{translate('zone_name')}} *"
                                                                   required value="{{old('name')}}">
                                                            <label>{{translate('zone_name')}} *</label>
                                                            <span class="material-icons">note_alt</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="lang[]" value="default">
                                            @endif

                                            @if(isset($parentZoneChoices))
                                                <div class="mb-30">
                                                    <label class="input-label d-block mb-2">{{ translate('Parent_zone') }}</label>
                                                    <select name="parent_id" class="form-select theme-input-style w-100">
                                                        <option value="">{{ translate('No_parent_root_zone') }}</option>
                                                        @foreach($parentZoneChoices as $pz)
                                                            <option value="{{ $pz->id }}" @selected(old('parent_id') == $pz->id)>{{ $pz->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            <div class="form-group mb-30">
                                                <label class="input-label d-block mb-2" for="zone-description">{{ translate('Zone_description') }}</label>
                                                <span class="input-label-secondary d-block mb-2 fs-12">{{ translate('Zone_description_hint') }}</span>
                                                <textarea name="description"
                                                          id="zone-description"
                                                          class="form-control theme-input-style"
                                                          rows="5"
                                                          placeholder="{{ translate('Zone_description_placeholder') }}">{{ old('description') }}</textarea>
                                            </div>

                                            <div class="form-group mb-3 coordinates">
                                                <label class="input-label"
                                                       for="exampleFormControlInput1">{{translate('coordinates')}}
                                                    <span
                                                        class="input-label-secondary">{{translate('draw_your_zone_on_the_map')}}</span>
                                                </label>
                                                <textarea type="text" rows="8" name="coordinates" id="coordinates"
                                                          class="form-control" readonly></textarea>
                                            </div>

                                            <div class="map-warper map__zone-setup dark-support rounded overflow-hidden">
                                                <input id="pac-input" class="controls rounded search_area"
                                                       title="{{translate('search_your_location_here')}}" type="text"
                                                       placeholder="{{translate('search_here')}}"/>
                                                <div class="map_canvas" id="map-canvas"></div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex justify-content-end gap-3 mt-30">
                                                <button class="btn btn--secondary" type="reset"
                                                        id="reset_btn">{{translate('reset')}}</button>
                                                <button class="btn btn--primary"
                                                        type="submit">{{translate('submit')}}</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endcan

                    <div class="d-flex justify-content-end border-bottom mx-lg-4 mb-10">
                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{translate('Total_Zones')}}:</span>
                            <span class="title-color" id="totalListCount">{{ $zones->total() }}</span>
                        </div>
                    </div>

                    <div class="card mb-30">
                        <div class="card-body">
                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                <form action="{{url()->current()}}" class="search-form search-form_style-two"  method="GET">
                                    <div class="input-group search-form__input_group">
                                            <span class="search-form__icon">
                                                <span class="material-icons">search</span>
                                            </span>
                                        <input type="search" class="theme-input-style search-form__input zone-search-input"
                                               value="{{$search}}" name="search"
                                               placeholder="{{translate('search_here')}}">
                                    </div>
                                    <button type="submit" class="btn btn--primary">{{translate('search')}}</button>
                                </form>

                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    @can('zone_export')
                                        <div class="dropdown">
                                            <button type="button"
                                                    class="btn btn--secondary text-capitalize dropdown-toggle"
                                                    data-bs-toggle="dropdown">
                                                <span
                                                    class="material-icons">file_download</span> {{translate('download')}}
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                                <li><a class="dropdown-item"
                                                       href="{{route('admin.zone.download')}}?search={{$search}}">{{translate('excel')}}</a>
                                                </li>
                                            </ul>
                                        </div>
                                    @endcan
                                </div>
                            </div>

                            <div id="ListTableContainer">
                                @include('zonemanagement::admin.partials._table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="offset" value="{{ request()->page }}">

@endsection

@push('script')
    <script src="{{asset('assets/admin-module/plugins/dataTables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('assets/admin-module/plugins/dataTables/dataTables.select.min.js')}}"></script>

    @php
        $api_key = optional(business_config('google_map', 'third_party'))->live_values ?? [];
        $zoneVectorMapId = trim((string) ($api_key['map_id'] ?? ''));
    @endphp
    <script src="https://maps.googleapis.com/maps/api/js?key={{$api_key['map_api_key_client'] ?? ''}}&libraries=drawing,places,geometry&v=beta"></script>

    <script>
        "use strict";

        const ZONE_PARENT_GEO_URL = "{{ url('/admin/zone/parent-geometry') }}";
        const ZONE_BOUNDARY_FROM_PLACE_URL = "{{ route('admin.zone.boundary-from-place') }}";
        const ZONE_VECTOR_MAP_ID = @json($zoneVectorMapId);
        const MSG_CHILD_OUTSIDE_PARENT = @json(translate('Child_zone_must_be_inside_parent_boundary'));

        const ZONE_GREEN_STYLE = {
            strokeColor: '#2e7d32',
            strokeOpacity: 1,
            strokeWeight: 2,
            fillColor: '#43a047',
            fillOpacity: 0.28,
        };

        function auto_grow() {
            // Keep textarea height in sync with polygon vertex count.
            let element = document.getElementById("coordinates");
            if (!element) return;
            element.style.height = "5px";
            element.style.height = (element.scrollHeight) + "px";
        }

        let map; // Global declaration of the map
        let drawingManager;
        let lastPolygon = null;
        let polygons = [];
        let parentBoundaryPolygon = null;
        let currentLocationMarker = null;

        function addCurrentLocationControl() {
            if (!map) return;

            const controlDiv = document.createElement('div');
            controlDiv.style.margin = '8px';

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn--primary';
            button.style.display = 'flex';
            button.style.alignItems = 'center';
            button.style.gap = '6px';
            button.style.padding = '6px 10px';
            button.style.borderRadius = '6px';
            button.style.boxShadow = '0 2px 6px rgba(0,0,0,.2)';
            button.title = 'Go to current location';
            button.innerHTML = '<span class="material-icons" style="font-size:18px;line-height:18px;">my_location</span><span>Current</span>';

            button.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    toastr.warning('Geolocation not supported by this browser.');
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const pos = { lat: position.coords.latitude, lng: position.coords.longitude };
                        map.setCenter(pos);
                        map.setZoom(14);
                        if (currentLocationMarker) {
                            currentLocationMarker.setMap(null);
                        }
                        currentLocationMarker = new google.maps.Marker({
                            position: pos,
                            map,
                            title: 'Current location',
                        });
                    },
                    function () {
                        toastr.warning('Unable to access current location. Please allow location permission.');
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            });

            controlDiv.appendChild(button);
            map.controls[google.maps.ControlPosition.TOP_RIGHT].push(controlDiv);
        }

        function loadParentBoundary(zoneId) {
            if (!map) {
                return;
            }
            if (parentBoundaryPolygon) {
                parentBoundaryPolygon.setMap(null);
                parentBoundaryPolygon = null;
            }
            if (!zoneId) {
                return;
            }
            $.getJSON(ZONE_PARENT_GEO_URL + '/' + zoneId)
                .done(function (data) {
                    if (!data.paths || !data.paths.length) {
                        toastr.warning(@json(translate('Parent_zone_has_no_drawn_boundary')));
                        return;
                    }
                    parentBoundaryPolygon = new google.maps.Polygon({
                        paths: data.paths,
                        strokeColor: '#1a7f37',
                        strokeOpacity: 0.95,
                        strokeWeight: 2,
                        fillColor: '#1a7f37',
                        fillOpacity: 0.12,
                        clickable: false,
                        zIndex: 1,
                    });
                    parentBoundaryPolygon.setMap(map);
                })
                .fail(function () {
                    toastr.warning(@json(translate('Could_not_load_parent_zone_boundary')));
                });
        }

        function validateChildInsideParentIfNeeded(childPolygon) {
            if (!childPolygon || !parentBoundaryPolygon) {
                return true;
            }
            const path = childPolygon.getPath();
            if (!path || path.getLength() < 3) {
                return true;
            }
            for (let i = 0; i < path.getLength(); i++) {
                if (!google.maps.geometry.poly.containsLocation(path.getAt(i), parentBoundaryPolygon)) {
                    return false;
                }
            }
            return true;
        }

        function attachChildPolygonPathListeners(polygon) {
            if (!polygon || polygon.__pkZonePathHooked) {
                return;
            }
            polygon.__pkZonePathHooked = true;
            const path = polygon.getPath();
            const sync = function () {
                $('#coordinates').val(path.getArray());
                auto_grow();
                if (!validateChildInsideParentIfNeeded(polygon)) {
                    toastr.warning(MSG_CHILD_OUTSIDE_PARENT);
                }
            };
            google.maps.event.addListener(path, 'set_at', sync);
            google.maps.event.addListener(path, 'insert_at', sync);
            google.maps.event.addListener(path, 'remove_at', sync);
        }

        function rectanglePathFromLatLngBounds(bounds) {
            const ne = bounds.getNorthEast();
            const sw = bounds.getSouthWest();
            const nw = new google.maps.LatLng(ne.lat(), sw.lng());
            const se = new google.maps.LatLng(sw.lat(), ne.lng());
            return [nw, ne, se, sw];
        }

        function boundsAroundLocation(loc, deltaDeg) {
            const lat = typeof loc.lat === 'function' ? loc.lat() : loc.lat;
            const lng = typeof loc.lng === 'function' ? loc.lng() : loc.lng;
            return new google.maps.LatLngBounds(
                new google.maps.LatLng(lat - deltaDeg, lng - deltaDeg),
                new google.maps.LatLng(lat + deltaDeg, lng + deltaDeg)
            );
        }

        function fitMapToBoundaryPaths(paths) {
            if (!paths || paths.length < 2 || !map) {
                return;
            }
            const b = new google.maps.LatLngBounds();
            paths.forEach((p) => b.extend(new google.maps.LatLng(Number(p.lat), Number(p.lng))));
            map.fitBounds(b);
        }

        /**
         * Irregular administrative outline (same source family as boundaries on Google Maps).
         * Loaded via Nominatim/OSM through the server; Google does not expose this polygon in the JS API.
         */
        function replaceZoneWithAdministrativeBoundaryPaths(paths) {
            if (!paths || paths.length < 3) {
                return false;
            }
            const latLngPath = paths.map((p) => ({
                lat: Number(p.lat),
                lng: Number(p.lng),
            }));
            const poly = new google.maps.Polygon(Object.assign({}, ZONE_GREEN_STYLE, {
                paths: latLngPath,
                editable: false,
            }));
            if (!validateChildInsideParentIfNeeded(poly)) {
                toastr.error(MSG_CHILD_OUTSIDE_PARENT);
                return false;
            }
            if (lastPolygon) {
                lastPolygon.setMap(null);
            }
            lastPolygon = poly;
            lastPolygon.setMap(map);
            attachChildPolygonPathListeners(lastPolygon);
            $('#coordinates').val(lastPolygon.getPath().getArray());
            auto_grow();
            return true;
        }

        /**
         * Fallback: rectangle from Geocoder bounds/viewport only when no admin polygon is available.
         */
        function replaceZoneWithGeocodedBounds(geoBounds) {
            if (!geoBounds || geoBounds.isEmpty()) {
                return false;
            }
            const path = rectanglePathFromLatLngBounds(geoBounds);
            const poly = new google.maps.Polygon(Object.assign({}, ZONE_GREEN_STYLE, {
                paths: path,
                editable: true,
            }));
            if (!validateChildInsideParentIfNeeded(poly)) {
                toastr.error(MSG_CHILD_OUTSIDE_PARENT);
                return false;
            }
            if (lastPolygon) {
                lastPolygon.setMap(null);
            }
            lastPolygon = poly;
            lastPolygon.setMap(map);
            attachChildPolygonPathListeners(lastPolygon);
            $('#coordinates').val(lastPolygon.getPath().getArray());
            auto_grow();
            return true;
        }

        function resetMap(controlDiv) {
            // Set CSS for the control border.
            const controlUI = document.createElement("div");
            controlUI.style.backgroundColor = "#fff";
            controlUI.style.border = "2px solid #fff";
            controlUI.style.borderRadius = "3px";
            controlUI.style.boxShadow = "0 2px 6px rgba(0,0,0,.3)";
            controlUI.style.cursor = "pointer";
            controlUI.style.marginTop = "8px";
            controlUI.style.marginBottom = "22px";
            controlUI.style.textAlign = "center";
            controlUI.title = "Reset map";
            controlDiv.appendChild(controlUI);
            // Set CSS for the control interior.
            const controlText = document.createElement("div");
            controlText.style.color = "rgb(25,25,25)";
            controlText.style.fontFamily = "Roboto,Arial,sans-serif";
            controlText.style.fontSize = "10px";
            controlText.style.lineHeight = "16px";
            controlText.style.paddingLeft = "2px";
            controlText.style.paddingRight = "2px";
            controlText.innerHTML = "X";
            controlUI.appendChild(controlText);
            // Setup the click event listeners: simply set the map to Chicago.
            controlUI.addEventListener("click", () => {
                if (lastPolygon) {
                    lastPolygon.setMap(null);
                }
                lastPolygon = null;
                $('#coordinates').val('');
            });
        }

        function initialize() {
            let myLatLng = {
                lat: 23.757989,
                lng: 90.360587
            };

            let myOptions = {
                zoom: 10,
                center: myLatLng,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
            };
            if (ZONE_VECTOR_MAP_ID) {
                myOptions.mapId = ZONE_VECTOR_MAP_ID;
            }
            map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);
            const geocoder = new google.maps.Geocoder();
            drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: google.maps.drawing.OverlayType.POLYGON,
                drawingControl: true,
                drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: [google.maps.drawing.OverlayType.POLYGON]
                },
                polygonOptions: Object.assign({}, ZONE_GREEN_STYLE, { editable: true })
            });
            drawingManager.setMap(map);
            // Try HTML5 geolocation.
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };
                        map.setCenter(pos);
                    });
            }
            addCurrentLocationControl();

            google.maps.event.addListener(drawingManager, "overlaycomplete", function (event) {
                if (lastPolygon) {
                    lastPolygon.setMap(null);
                }
                const overlay = event.overlay;
                if (!validateChildInsideParentIfNeeded(overlay)) {
                    overlay.setMap(null);
                    toastr.error(MSG_CHILD_OUTSIDE_PARENT);
                    return;
                }
                $('#coordinates').val(overlay.getPath().getArray());
                lastPolygon = overlay;
                attachChildPolygonPathListeners(lastPolygon);
                auto_grow();
            });

            const resetDiv = document.createElement("div");
            resetMap(resetDiv, lastPolygon);
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(resetDiv);

            $(document).on('change', 'select[name="parent_id"]', function () {
                loadParentBoundary($(this).val());
            });
            const initialParent = $('select[name="parent_id"]').val();
            if (initialParent) {
                loadParentBoundary(initialParent);
            }

            // Create the search box and link it to the UI element.
            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
            // Bias the SearchBox results towards current map's viewport.
            map.addListener("bounds_changed", () => {
                searchBox.setBounds(map.getBounds());
            });
            let markers = [];

            // Listen for the event fired when the user selects a prediction and retrieve
            // more details for that place.
            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();

                if (places.length === 0) {
                    return;
                }
                markers.forEach((marker) => {
                    marker.setMap(null);
                });
                markers = [];

                const bounds = new google.maps.LatLngBounds();
                places.forEach((place) => {
                    if (!place.geometry || !place.geometry.location) {
                        return;
                    }
                    if (place.geometry.viewport) {
                        bounds.union(place.geometry.viewport);
                    } else if (place.geometry.bounds) {
                        bounds.union(place.geometry.bounds);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });

                const primary = places.find((p) => p.geometry && p.geometry.location);
                const geocodeRequest = primary && primary.place_id
                    ? { placeId: primary.place_id }
                    : primary && (primary.formatted_address || primary.name)
                        ? { address: primary.formatted_address || primary.name }
                        : null;

                const showMarkersFallback = () => {
                    places.forEach((place) => {
                        if (!place.geometry || !place.geometry.location) {
                            return;
                        }
                        const icon = {
                            url: place.icon,
                            size: new google.maps.Size(71, 71),
                            origin: new google.maps.Point(0, 0),
                            anchor: new google.maps.Point(17, 34),
                            scaledSize: new google.maps.Size(25, 25),
                        };
                        markers.push(
                            new google.maps.Marker({
                                map,
                                icon,
                                title: place.name,
                                position: place.geometry.location,
                            })
                        );
                    });
                    map.fitBounds(bounds);
                };

                if (!geocodeRequest || !primary) {
                    showMarkersFallback();
                    return;
                }

                geocoder.geocode(geocodeRequest, (results, status) => {
                    if (status !== google.maps.GeocoderStatus.OK || !results || !results[0]) {
                        showMarkersFallback();
                        return;
                    }
                    const g = results[0].geometry;
                    const geoBounds =
                        g.bounds ||
                        g.viewport ||
                        (g.location ? boundsAroundLocation(g.location, 0.006) : null);
                    if (!geoBounds || geoBounds.isEmpty()) {
                        showMarkersFallback();
                        return;
                    }

                    const boundaryQuery =
                        (results[0].formatted_address || '').trim() ||
                        (primary.formatted_address || '').trim() ||
                        (primary.name || '').trim();

                    const applyRectFallback = () => {
                        map.fitBounds(geoBounds);
                        if (!replaceZoneWithGeocodedBounds(geoBounds)) {
                            showMarkersFallback();
                        }
                    };

                    if (!boundaryQuery) {
                        applyRectFallback();
                        return;
                    }

                    const loc = g.location;
                    const boundaryParams = { q: boundaryQuery };
                    const placeName = (primary.name || '').trim();
                    if (placeName && placeName.toLowerCase() !== boundaryQuery.toLowerCase()) {
                        boundaryParams.name = placeName;
                    }
                    const addrComps = results[0].address_components || [];
                    for (let ac = 0; ac < addrComps.length; ac++) {
                        const types = addrComps[ac].types || [];
                        if (types.indexOf('country') !== -1 && addrComps[ac].short_name) {
                            boundaryParams.countrycodes = String(addrComps[ac].short_name).toLowerCase();
                            break;
                        }
                    }
                    if (loc) {
                        boundaryParams.lat = typeof loc.lat === 'function' ? loc.lat() : loc.lat;
                        boundaryParams.lng = typeof loc.lng === 'function' ? loc.lng() : loc.lng;
                    }

                    $.getJSON(ZONE_BOUNDARY_FROM_PLACE_URL, boundaryParams)
                        .done(function (data) {
                            if (data.paths && data.paths.length >= 3) {
                                fitMapToBoundaryPaths(data.paths);
                                if (replaceZoneWithAdministrativeBoundaryPaths(data.paths)) {
                                    return;
                                }
                            }
                            applyRectFallback();
                        })
                        .fail(function () {
                            applyRectFallback();
                        });
                });
            });
        }

        // Some pages load this script after the window `load` event.
        // Calling initialize immediately (when possible) avoids a broken drawing manager.
        if (typeof google !== 'undefined' && google.maps && document.getElementById("map-canvas")) {
            initialize();
        } else {
            window.addEventListener('load', initialize);
        }


        $('#reset_btn').click(function (e) {
            e.preventDefault();

            $('input[name="name[]"]').val('');

            $('#coordinates').val('');
            if (lastPolygon) {
                lastPolygon.setMap(null);
                lastPolygon = null;
            }

            // Re-center the map to default location
            if (map) {
                map.setCenter({ lat: 23.757989, lng: 90.360587 });
                map.setZoom(10);
            }

            $('#pac-input').val('');
        });


        function performValidation(event) {
            if (!lastPolygon) {
                event.preventDefault();
                toastr.warning('{{ translate('Please draw your zone on the map') }}', {
                    CloseButton: true,
                    ProgressBar: true,
                });
                return;
            }
            if (!validateChildInsideParentIfNeeded(lastPolygon)) {
                event.preventDefault();
                toastr.error(MSG_CHILD_OUTSIDE_PARENT);
            }
        }

        $('#zone-form').submit(function (event) {
            performValidation(event);
        });

        $('#pac-input').keydown(function (event) {
            if (event.keyCode === 13) {
                performValidation(event);
            }
        });

        $(".lang_link").on('click', function (e) {
            e.preventDefault();
            $(".lang_link").removeClass('active');
            $(".lang-form").addClass('d-none');
            $(this).addClass('active');

            let form_id = this.id;
            let lang = form_id.substring(0, form_id.length - 5);
            $("#" + lang + "-form").removeClass('d-none');
        });

        let statusSelectedItem;
        let statusSelectedRoute;
        let statusInitialState;

        $('.nav-link').on('click', function () {
            const urlParams = new URLSearchParams($(this).attr('href').split('?')[1]);
        });

        $(document).on('change', '.status-update', function (e) {
            // Prevent default toggle behavior to avoid checkbox jumping
            e.preventDefault();
            e.stopImmediatePropagation();

            statusSelectedItem = $(this);
            statusInitialState = statusSelectedItem.prop('checked'); // Get current state (true if ON)

            // Immediately revert the checkbox visually until confirmation
            statusSelectedItem.prop('checked', !statusInitialState);

            let itemId = statusSelectedItem.data('id');
            statusSelectedRoute = '{{ route('admin.zone.status-update', ['id' => ':itemId']) }}'.replace(':itemId', itemId);

            let confirmationTitleText = statusInitialState
                ? '{{ translate('Are you sure to Turn On the Zone Status') }}?'
                : '{{ translate('Are you sure to Turn Off the Zone Status') }}?';

            $('.confirmation-title-text').text(confirmationTitleText);

            let confirmationDescriptionText = statusInitialState
                ? '{{ translate('Once you turn on the Zone Status, the user can find the category, services, and location in that zone') }}.'
                : '{{ translate('Once you turn off the Zone Status it will impact the category, services, and location finding for customers') }}.';

            $('.confirmation-description-text').text(confirmationDescriptionText);

            let imgSrc = statusInitialState
                ? "{{ asset('assets/admin-module/img/icons/status-on.png') }}"
                : "{{ asset('assets/admin-module/img/icons/status-off.png') }}";

            $('#confirmChangeModal img').attr('src', imgSrc);

            showModal();
        });

        $('#confirmChange').on('click', function () {
            updateStatus(statusSelectedRoute);
        });

        $('.cancel-change').on('click', function () {
            resetCheckboxState();
            hideModal();
        });

        $('#confirmChangeModal').on('hidden.bs.modal', function () {
            resetCheckboxState();
        });

        function showModal() {
            $('#confirmChangeModal').modal('show');
        }

        function hideModal() {
            $('#confirmChangeModal').modal('hide');
        }

        //  Reverts checkbox if user cancels
        function resetCheckboxState() {
            if (statusSelectedItem) {
                statusSelectedItem.prop('checked', !statusInitialState);
            }
        }

        //  AJAX update - triggers only if user confirms
        function updateStatus(route) {
            let page = $('#offset').val();
            $.ajax({
                url: route,
                type: 'POST',
                data: {_token: '{{ csrf_token() }}'},
                dataType: 'json',
                success: function (data) {
                    toastr.success(data.message, {
                        CloseButton: true,
                        ProgressBar: true
                    });

                    // Update UI manually or reload table as needed
                    reloadTable(page); // Optional - if backend changes are needed
                    hideModal();
                },
                error: function () {
                    resetCheckboxState();
                    toastr.error('Something went wrong! Please try again.');
                }
            });
        }

        function zoneListToggleButtonSetCollapsed($btn) {
            if (!$btn || !$btn.length) {
                return;
            }
            $btn.attr('aria-expanded', 'false');
            $btn.removeClass('zone-toggle-children--hide').addClass('zone-toggle-children--view');
            $btn.find('.zone-toggle-children__icon').text('expand_more');
            $btn.find('.zone-toggle-children__label').text($btn.data('label-show'));
        }

        function zoneListToggleButtonSetExpanded($btn) {
            if (!$btn || !$btn.length) {
                return;
            }
            $btn.attr('aria-expanded', 'true');
            $btn.removeClass('zone-toggle-children--view').addClass('zone-toggle-children--hide');
            $btn.find('.zone-toggle-children__icon').text('expand_less');
            $btn.find('.zone-toggle-children__label').text($btn.data('label-hide'));
        }

        function zoneListNestContiguousRuns(parentZoneId) {
            const $rows = $('tr.zone-list-tree-row[data-child-of="' + parentZoneId + '"]').filter(':not(.d-none)');
            const runs = [];
            let run = [];
            $rows.each(function () {
                const el = this;
                if (run.length === 0) {
                    run.push(el);
                    return;
                }
                const last = run[run.length - 1];
                if (last.nextElementSibling === el) {
                    run.push(el);
                } else {
                    runs.push(run);
                    run = [el];
                }
            });
            if (run.length) {
                runs.push(run);
            }
            return runs;
        }

        function zoneListRefreshBranchHighlight() {
            $('tr.zone-list-tree-row').removeClass(
                'zone-branch-subtree zone-branch-nest zone-branch-nest--l1 zone-branch-nest--l2 zone-branch-nest--l3 zone-branch-nest--l4 zone-branch-nest-first zone-branch-nest-last'
            );
            const $openTop = $('tr.zone-list-top-level.zone-list-expandable.zone-children-open').first();
            if (!$openTop.length) {
                return;
            }
            const rootId = $openTop.attr('data-branch-root');
            if (!rootId) {
                return;
            }
            const $subtree = $('tr.zone-list-tree-row[data-branch-root="' + rootId + '"]').filter(function () {
                if ($(this).hasClass('d-none')) {
                    return false;
                }
                const co = $(this).attr('data-child-of');
                return co !== undefined && co !== '';
            });
            $subtree.addClass('zone-branch-subtree');

            $('tr.zone-list-expandable.zone-children-open').each(function () {
                const $p = $(this);
                const pid = $p.data('zone-id');
                if (!pid) {
                    return;
                }
                const pDepth = parseInt($p.attr('data-depth'), 10);
                const depth = Number.isNaN(pDepth) ? 0 : pDepth;
                const lvl = Math.min(depth + 1, 4);
                const runs = zoneListNestContiguousRuns(pid);
                runs.forEach(function (elements) {
                    const $r = $(elements);
                    $r.addClass('zone-branch-nest zone-branch-nest--l' + lvl);
                    $r.first().addClass('zone-branch-nest-first');
                    $r.last().addClass('zone-branch-nest-last');
                });
            });
        }

        function zoneListCloseBranch($row) {
            const id = $row.data('zone-id');
            $row.removeClass('zone-children-open');
            const $btn = $row.find('.zone-toggle-children');
            zoneListToggleButtonSetCollapsed($btn);
            $('tr.zone-list-tree-row[data-child-of="' + id + '"]').each(function () {
                const $child = $(this);
                if ($child.hasClass('zone-list-expandable') && $child.hasClass('zone-children-open')) {
                    zoneListCloseBranch($child);
                }
                $child.addClass('d-none');
            });
            zoneListRefreshBranchHighlight();
        }

        function zoneListOpenBranch($row) {
            const parentId = $row.attr('data-child-of');
            const hasParent = parentId !== undefined && parentId !== '';

            if (!hasParent) {
                $('tr.zone-list-top-level.zone-list-expandable.zone-children-open').not($row).each(function () {
                    zoneListCloseBranch($(this));
                });
            } else {
                $('tr.zone-list-expandable.zone-children-open[data-child-of="' + parentId + '"]').not($row).each(function () {
                    zoneListCloseBranch($(this));
                });
            }

            const id = $row.data('zone-id');
            $row.addClass('zone-children-open');
            $('tr.zone-list-tree-row[data-child-of="' + id + '"]').removeClass('d-none');
            const $openBtn = $row.find('.zone-toggle-children');
            zoneListToggleButtonSetExpanded($openBtn);
            zoneListRefreshBranchHighlight();
        }

        function zoneListToggleBranch($row) {
            const has = $row.data('has-children') === 1 || $row.attr('data-has-children') === '1';
            if (!has) {
                return;
            }
            if ($row.hasClass('zone-children-open')) {
                zoneListCloseBranch($row);
            } else {
                zoneListOpenBranch($row);
            }
        }

        $(document).on('click', '.zone-toggle-children', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const $expandRow = $(this).closest('tr.zone-list-expandable');
            zoneListToggleBranch($expandRow);
        });

        $(document).on('click', '.zone-list-expandable', function (e) {
            if ($(e.target).closest('a, button, label, input, .switcher').length) {
                return;
            }
            zoneListToggleBranch($(this));
        });

        function reloadTable(page) {
            let search = $('.zone-search-input').val();
            $.ajax({
                url: "{{ route('admin.zone.table') }}",
                type: "GET",
                data: {
                    search: search,
                    page: page
                },
                success: function (response) {
                    if (response.page != page) {
                        updateBrowserUrl(search, response.page);
                        $('#offset').val((response.page - 1) * {{ pagination_limit() }});
                    } else {
                        $('#offset').val(response.offset);
                        updateBrowserUrl(search, page);
                    }

                    $('#totalListCount').html(response.totalCount)
                    $('#ListTableContainer').empty().html(response.view);
                    zoneListRefreshBranchHighlight();
                },
                error: function () {
                    toastr.error('Failed to update table. Please reload the page.', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        }

        function updateBrowserUrl(search, page) {
            const params = new URLSearchParams();
            if (search) params.set('search', search);
            if (page > 1) params.set('page', page);

            const newUrl = `${window.location.pathname}?${params.toString()}`;
            window.history.replaceState({}, '', newUrl);
        }

        $(function () {
            zoneListRefreshBranchHighlight();
        });

        // Toggle zone form visibility (default: list only)
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('add-zone-form-btn');
            const wrapper = document.getElementById('zone-form-wrapper');

            if (!btn || !wrapper) return;

            btn.addEventListener('click', function () {
                wrapper.classList.remove('d-none');
                btn.classList.add('d-none');
                // Google Maps sometimes renders incorrectly when initialized while hidden.
                if (typeof google !== 'undefined' && google?.maps?.event && typeof map !== 'undefined' && map) {
                    google.maps.event.trigger(map, 'resize');
                }
                wrapper.scrollIntoView({behavior: 'smooth', block: 'start'});
            });
        });
    </script>
@endpush
