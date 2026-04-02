<div class="modal fade" id="serviceAddressModal--{{$booking['id']}}" tabindex="-1" aria-labelledby="serviceAddressModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{route('admin.booking.service_address_update', [$booking['service_address_id']])}}"
              method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0 m-4">
                    <div class="d-flex flex-column gap-2 align-items-center">
                        <img width="75" class="mb-2"
                             src="{{asset('assets/provider-module')}}/img/media/address.jpg"
                             alt="">
                        <h3>{{translate('Update customer service address')}}</h3>

                        <div class="row mt-4 w-100">
                            <div class="col-12">
                                <div class="mb-30">
                                    <div class="form-floating">
                                        <textarea class="form-control" name="address" id="address" style="height: 5rem"
                                                  placeholder="{{translate('address')}} *"
                                                  required>{{$customerAddress?->address}}</textarea>
                                        <label>{{translate('address')}} *</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-30">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="address_label"
                                               placeholder="{{translate('Address_Label')}} *"
                                               value="{{$customerAddress?->address_label}}" required>
                                        <label>{{translate('Address_Label')}} *</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-30">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="landmark"
                                               placeholder="{{translate('Landmark')}}"
                                               value="{{$customerAddress?->landmark}}">
                                        <label>{{translate('Landmark')}} ({{translate('Optional')}})</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-30">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="latitude" id="latitude"
                                               placeholder="{{translate('lat')}}"
                                               value="{{$customerAddress?->lat}}"
                                               data-bs-toggle="tooltip" data-bs-placement="top"
                                               title="{{translate('Select from map')}}">
                                        <label>{{translate('lat')}} ({{translate('Optional')}})</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-30">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="longitude" id="longitude"
                                               placeholder="{{translate('lon')}}"
                                               value="{{$customerAddress?->lon}}"
                                               data-bs-toggle="tooltip" data-bs-placement="top"
                                               title="{{translate('Select from map')}}">
                                        <label>{{translate('lon')}} ({{translate('Optional')}})</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-30">
                                    <select class="js-select select-zone theme-input-style w-100" name="zone_id">
                                        <option value="">{{translate('Select zone')}} ({{translate('Optional')}})</option>
                                        @foreach($zones as $zone)
                                            <option value="{{$zone?->id}}" {{$zone?->id == $customerAddress?->zone_id ? 'selected' : null}}>{{$zone?->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div id="location_map_div" class="location_map_class">
                                <input id="pac-input" class="form-control w-auto"
                                       data-toggle="tooltip"
                                       data-placement="right"
                                       data-original-title="{{ translate('search_your_location_here') }}"
                                       type="text" placeholder="{{ translate('search_here') }}"/>
                                <div id="location_map_canvas"
                                     class="overflow-hidden rounded canvas_class"></div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-end gap-3 border-0 pt-0 pb-4 m-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal" aria-label="Close">
                        {{translate('Cancel')}}</button>
                    <button type="submit" class="btn btn--primary">{{translate('Update')}}</button>
                </div>
            </div>
        </form>
    </div>
</div>
