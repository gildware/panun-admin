@extends('adminmodule::layouts.master')

@section('title',translate('zone_edit'))

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
                        <h2 class="page-title">{{translate('zone_update')}}</h2>
                    </div>

                    <div class="card zone-setup-instructions mb-30">
                        <div class="card-body p-30">
                            <form action="{{route('admin.zone.update',[$zone->id])}}" enctype="multipart/form-data"
                                  method="POST">
                                @csrf
                                @method('PUT')
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
                                                    <p>{{translate('click_this_icon_to_start_pin_points_in_the_map_and_connect_them_
                                                        to_draw_a_
                                                        zone_._Minimum_3_points_required')}}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="map-img mt-4">
                                                <img src="{{asset('assets/admin-module/img/instructions.gif')}}"
                                                     alt="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-7">
                                        @php($language = Modules\BusinessSettingsModule\Entities\BusinessSettings::where('key_name','system_language')->first())
                                        @php($default_lang = str_replace('_', '-', app()->getLocale()))
                                        @php($zoneLanguageTabs = $language ? collect($language->live_values ?? []) : collect())
                                        @if($language)
                                            <ul class="nav nav--tabs border-color-primary mb-4">
                                                <li class="nav-item">
                                                    <a class="nav-link lang_link active"
                                                       href="#"
                                                       id="default-link">{{translate('default')}}</a>
                                                </li>
                                                @foreach ($zoneLanguageTabs as $lang)
                                                    <li class="nav-item">
                                                        <a class="nav-link lang_link"
                                                           href="#"
                                                           id="{{ $lang['code'] }}-link">{{ get_language_name($lang['code']) }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if($language)
                                            <div class="form-floating form-floating__icon mb-30 lang-form" id="default-form">
                                                <input type="text" name="name[]" class="form-control"
                                                       placeholder="{{translate('zone_name')}}"
                                                       value="{{$zone?->getRawOriginal('name')}}" required>
                                                <label>{{translate('zone_name')}} ({{ translate('default') }})</label>
                                                <span class="material-icons">note_alt</span>
                                            </div>
                                            <input type="hidden" name="lang[]" value="default">
                                            @foreach ($zoneLanguageTabs as $lang)
                                                @php($translatedZoneName = collect($zone->translations ?? [])->first(fn ($t) => ($t->locale ?? '') === ($lang['code'] ?? '') && ($t->key ?? '') === 'zone_name')?->value ?? '')
                                                <div class="form-floating form-floating__icon mb-30 d-none lang-form"
                                                     id="{{$lang['code']}}-form">
                                                    <input type="text" name="name[]" class="form-control"
                                                           placeholder="{{translate('zone_name')}}"
                                                           value="{{ $translatedZoneName }}">
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
                                                               required value="{{$zone->name}}">
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
                                                        <option value="{{ $pz->id }}" @selected(old('parent_id', $zone->parent_id ?? '') == $pz->id)>{{ $pz->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif

                                        <div class="form-group mb-3 coordinates">
                                            <label class="input-label"
                                                   for="exampleFormControlInput1">{{translate('coordinates')}}
                                                <span
                                                    class="input-label-secondary">{{translate('draw_your_zone_on_the_map')}}</span>
                                            </label>

                                            <textarea type="text" rows="8" name="coordinates" id="coordinates"
                                                      class="form-control" readonly>
                                                @foreach($area['coordinates'] ?? [] as $key=>$coords)<?php if (count($area['coordinates'] ?? []) != $key + 1){if ($key != 0) echo(','); ?>({{$coords[1]}},{{$coords[0]}})<?php } ?>@endforeach
                                            </textarea>
                                        </div>

                                        <div class="map-warper overflow-hidden map_area">
                                            <input id="pac-input" class="controls rounded search_area"
                                                   title="{{translate('search_your_location_here')}}" type="text"
                                                   placeholder="{{translate('search_here')}}"/>
                                            <div class="map_canvas" id="map-canvas"></div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end gap-20 mt-30">
                                            <button class="btn btn--secondary" type="reset"
                                                    id="reset_btn">{{translate('reset')}}</button>
                                            <button class="btn btn--primary"
                                                    type="submit">{{translate('update')}}</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @php($api_key=(business_config('google_map', 'third_party'))->live_values)
    <script src="https://maps.googleapis.com/maps/api/js?key={{$api_key['map_api_key_client']}}&libraries=drawing,places,geometry&v=beta"></script>

    <script>
        "use strict";
        auto_grow();

        const ZONE_PARENT_GEO_URL = "{{ url('/admin/zone/parent-geometry') }}";
        const MSG_CHILD_OUTSIDE_PARENT = @json(translate('Child_zone_must_be_inside_parent_boundary'));
        const initialZoneParentId = @json(old('parent_id', (string) ($zone->parent_id ?? '')));

        function auto_grow() {
            let element = document.getElementById("coordinates");
            element.style.height = "5px";
            element.style.height = (element.scrollHeight) + "px";
        }

        let map; // Global declaration of the map
        let lat_longs = new Array();
        let drawingManager;
        let lastpolygon = null;
        let zonePolygon = null;
        let bounds = new google.maps.LatLngBounds();
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
        // Boundary auto-overlay removed: zone boundary will be drawn manually.

        // Boundary auto-overlay removed: zone boundary will be drawn manually.

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

        function getEffectiveChildPolygonForValidation() {
            if (lastpolygon && lastpolygon.getMap && lastpolygon.getMap()) {
                return lastpolygon;
            }
            if (zonePolygon && zonePolygon.getMap && zonePolygon.getMap()) {
                return zonePolygon;
            }
            return null;
        }


        function resetMap(controlDiv) {
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

            const controlText = document.createElement("div");
            controlText.style.color = "rgb(25,25,25)";
            controlText.style.fontFamily = "Roboto,Arial,sans-serif";
            controlText.style.fontSize = "10px";
            controlText.style.lineHeight = "16px";
            controlText.style.paddingLeft = "2px";
            controlText.style.paddingRight = "2px";
            controlText.innerHTML = "X";
            controlUI.appendChild(controlText);
            controlUI.addEventListener("click", () => {
                if (lastpolygon) {
                    lastpolygon.setMap(null);
                }
                lastpolygon = null;
                $('#coordinates').val('');
            });
        }

        function initialize() {
            let myLatlng = new google.maps.LatLng({{ $centerLat }}, {{ $centerLng }});
            let myOptions = {
                zoom: 13,
                center: myLatlng,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);
            addCurrentLocationControl();

            const polygonCoords = [

                    @foreach($area['coordinates'] ?? [] as $coords)
                        {
                            lat: {{$coords[1]}}, lng: {{$coords[0]}}
                        },
                    @endforeach
            ];

            zonePolygon = new google.maps.Polygon({
                paths: polygonCoords,
                strokeColor: "#050df2",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillOpacity: 0,
            });

            zonePolygon.setMap(map);
            attachChildPolygonPathListeners(zonePolygon);

            zonePolygon.getPaths().forEach(function (path) {
                path.forEach(function (latlng) {
                    bounds.extend(latlng);
                    map.fitBounds(bounds);
                });
            });


            drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: google.maps.drawing.OverlayType.POLYGON,
                drawingControl: true,
                drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: [google.maps.drawing.OverlayType.POLYGON]
                },
                polygonOptions: {
                    editable: true
                }
            });
            drawingManager.setMap(map);

            google.maps.event.addListener(drawingManager, "overlaycomplete", function (event) {
                var newShape = event.overlay;
                newShape.type = event.type;
            });

            google.maps.event.addListener(drawingManager, "overlaycomplete", function (event) {
                if (lastpolygon) {
                    lastpolygon.setMap(null);
                }
                const overlay = event.overlay;
                if (!validateChildInsideParentIfNeeded(overlay)) {
                    overlay.setMap(null);
                    toastr.error(MSG_CHILD_OUTSIDE_PARENT);
                    return;
                }
                $('#coordinates').val(overlay.getPath().getArray());
                lastpolygon = overlay;
                attachChildPolygonPathListeners(lastpolygon);
                auto_grow();
            });
            const resetDiv = document.createElement("div");
            resetMap(resetDiv, lastpolygon);
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(resetDiv);

            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
            map.addListener("bounds_changed", () => {
                searchBox.setBounds(map.getBounds());
            });
            let markers = [];
            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();

                if (places.length == 0) {
                    return;
                }
                markers.forEach((marker) => {
                    marker.setMap(null);
                });
                markers = [];
                const bounds = new google.maps.LatLngBounds();
                places.forEach((place) => {
                    if (!place.geometry || !place.geometry.location) {
                        console.log("Returned place contains no geometry");
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

                    if (place.geometry.viewport) {
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });

            $(document).on('change', 'select[name="parent_id"]', function () {
                loadParentBoundary($(this).val());
            });
            if (initialZoneParentId) {
                loadParentBoundary(initialZoneParentId);
            }
        }

        // Some pages load this script after the window `load` event.
        // Initialize immediately when possible so drawing works.
        if (typeof google !== 'undefined' && google.maps && document.getElementById("map-canvas")) {
            initialize();
        } else {
            google.maps.event.addDomListener(window, 'load', initialize);
        }

        function set_all_zones() {
            $.get({
                url: '{{route('admin.zone.get-active-zones',[$zone->id])}}',
                dataType: 'json',
                success: function (data) {

                    for (var i = 0; i < data.length; i++) {
                        polygons.push(new google.maps.Polygon({
                            paths: data[i],
                            strokeColor: "#FF0000",
                            strokeOpacity: 0.8,
                            strokeWeight: 2,
                            fillColor: "#FF0000",
                            fillOpacity: 0.1,
                        }));
                        polygons[i].setMap(map);
                    }

                },
            });
        }

        $(document).on('ready', function () {
            set_all_zones();
        });

        $('#reset_btn').click(function () {
            $('#name').val(null);

            lastpolygon.setMap(null);
            $('#coordinates').val(null);
        })

        function performValidation(event) {
            const child = getEffectiveChildPolygonForValidation();
            if (!child) {
                event.preventDefault();
                toastr.warning('{{ translate('Please draw your zone on the map') }}');
                return;
            }
            if (!validateChildInsideParentIfNeeded(child)) {
                event.preventDefault();
                toastr.error(MSG_CHILD_OUTSIDE_PARENT);
            }
        }

        $('form').submit(function(event) {
            performValidation(event);
        });

        $('#pac-input').keydown(function(event) {
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
            console.log(lang);
            $("#" + lang + "-form").removeClass('d-none');
        });
    </script>
@endpush
