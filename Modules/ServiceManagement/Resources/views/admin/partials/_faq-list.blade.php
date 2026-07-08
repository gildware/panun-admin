<div class="service-faq-sortable mb-3" id="faqSortableList">
    @if($faqs->count() < 1)
        <div class="service-detail-faq-empty text-center py-4">
            <img src="{{asset('assets/admin-module/img/icons/faq.png')}}"
                 alt="{{ translate('faq') }}">
            <p class="text-muted">{{translate('no_faq_added_yet')}}</p>
        </div>
    @else
        <div class="d-flex align-items-center justify-content-between mb-2 px-1">
            <span class="text-muted fs-12">{{ translate('Drag_to_reorder') }}</span>
            <span class="text-muted fs-12">{{ $faqs->count() }} {{ translate('faq') }}</span>
        </div>
    @endif

    <div class="accordion" id="faqAccordionList">
        @foreach($faqs as $faq)
            <div class="service-faq-item" data-faq-id="{{ $faq->id }}">
                <form action="{{route('admin.faq.update',[$faq->id])}}" method="POST" class="mb-3 hide-div service-faq-edit-form"
                      id="edit-{{$faq->id}}">
                    @csrf
                    @method('PUT')
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" placeholder="{{translate('question')}}" name="question"
                               value="{{$faq->question}}"
                               required>
                        <label>{{translate('question')}}</label>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea class="form-control" placeholder="{{translate('answer')}}"
                                  name="answer" required style="min-height: 5.5rem;">{{$faq->answer}}</textarea>
                        <label>{{translate('answer')}}</label>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn--primary service-faq-update"
                                data-id="edit-{{$faq->id}}">
                            {{translate('update_faq')}}
                        </button>
                    </div>
                </form>

                <div class="accordion-item">
                    <div class="accordion-header d-flex flex-wrap flex-sm-nowrap gap-2 align-items-center"
                         id="heading-{{$faq->id}}">
                        <span class="material-icons service-faq-drag-handle"
                              draggable="true"
                              title="{{ translate('Drag_to_reorder') }}"
                              aria-label="{{ translate('Drag_to_reorder') }}">drag_indicator</span>
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq_{{$faq->id}}"
                                aria-expanded="false" aria-controls="faq_{{$faq->id}}">
                            {{$faq->question}}
                        </button>
                        <div class="btn-group d-flex gap-2 align-items-center">
                            <div>
                                @can('service_manage_status')
                                <label class="switcher" data-bs-toggle="modal" data-bs-target="#deactivateAlertModal">
                                    <input class="switcher_input service-ajax-status-update" type="checkbox" {{$faq->is_active?'checked':''}}
                                    data-route="{{route('admin.faq.status-update',[$faq->id])}}"
                                           data-id="faq-list">
                                    <span class="switcher_control"></span>
                                </label>
                                    @endcan
                            </div>
                            @can('service_update')
                            <button type="button"
                                    data-id="{{$faq->id}}"
                                    class="accordion-edit-btn bg-transparent border-0 p-0 show-service-edit-section"
                                    title="{{ translate('edit') }}">
                                <span class="material-icons">border_color</span>
                            </button>
                            @endcan
                            @can('service_delete')
                            <button type="button"
                                    class="accordion-delete-btn bg-transparent border-0 p-0 faq-list-ajax-delete"
                                    data-route="{{route('admin.faq.delete',[$faq->id,$faq->service_id])}}"
                                    title="{{ translate('delete') }}">
                                <span class="material-icons">delete</span>
                            </button>
                                @endcan
                        </div>
                    </div>
                    <div id="faq_{{$faq->id}}" class="accordion-collapse collapse"
                         aria-labelledby="heading-{{$faq->id}}" data-bs-parent="#faqAccordionList">
                        <div class="accordion-body">
                            {{$faq->answer}}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
