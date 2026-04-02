<div class="modal fade" id="customerAddressModal--{{$booking['id']}}" tabindex="-1" aria-labelledby="customerAddressModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form class="flex-grow-1" id="customerAddressModalSubmit">
            @csrf
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4 class="font-weight-bold">{{ translate('Change Service Location') }}</h4>

                    <div class="row mt-4">
                        <div class="col-md-6 col-12">
                            <div class="col-md-12 col-12">
                                <div id="location_map_div" class="location_map_class">
                                    <input id="address_pac-input" class="form-control w-auto"
                                           data-toggle="tooltip"
                                           data-placement="right"
                                           data-original-title="{{ translate('search_your_location_here') }}"
                                           type="text" placeholder="{{ translate('search_here') }}"/>
                                    <div id="address_location_map_canvas"
                                         class="overflow-hidden rounded canvas_class">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-12 row">
                            <div class="col-md-12 col-12">
                                <div class="mb-30">
                                    <div class="form-floating">
                                        <textarea class="form-control" name="address" id="address_address" style="height: 5rem"
                                                  placeholder="{{translate('address')}} *"
                                                  required>{{$booking->service_address?->address}}</textarea>
                                        <label>{{translate('address')}} *</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 col-12">
                                <div class="mb-30">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="address_label" id="address_address_label"
                                               placeholder="{{translate('Address_Label')}} *"
                                               value="{{$booking->service_address?->address_label}}" required>
                                        <label>{{translate('Address_Label')}} *</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 col-12">
                                <div class="mb-30">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="landmark" id="address_landmark"
                                               placeholder="{{translate('Landmark')}}"
                                               value="{{$booking->service_address?->landmark}}">
                                        <label>{{translate('Landmark')}} ({{translate('Optional')}})</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-30">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="latitude" id="address_latitude"
                                               placeholder="{{translate('lat')}}"
                                               value="{{$booking->service_address?->lat}}"
                                               data-bs-toggle="tooltip" data-bs-placement="top"
                                               title="{{translate('Select from map')}}">
                                        <label>{{translate('lat')}} ({{translate('Optional')}})</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-30">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="longitude" id="address_longitude"
                                               placeholder="{{translate('long')}}"
                                               value="{{$booking->service_address?->lon}}"
                                               data-bs-toggle="tooltip" data-bs-placement="top"
                                               title="{{translate('Select from map')}}">
                                        <label>{{translate('long')}} ({{translate('Optional')}})</label>
                                    </div>
                                </div>
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

