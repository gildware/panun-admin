<div class="service-faq-readonly mb-3">
    @if($faqs->count() < 1)
        <div class="service-detail-faq-empty text-center py-4">
            <img src="{{asset('assets/admin-module/img/icons/faq.png')}}"
                 alt="{{ translate('faq') }}">
            <p class="text-muted mb-0">{{translate('no_faq_added_yet')}}</p>
        </div>
    @else
        <div class="d-flex align-items-center justify-content-between mb-2 px-1">
            <span class="text-muted fs-12">{{ $faqs->count() }} {{ translate('faq') }}</span>
        </div>
        <div class="accordion" id="faqReadonlyAccordionList">
            @foreach($faqs as $faq)
                <div class="accordion-item mb-2">
                    <div class="accordion-header" id="faq-readonly-heading-{{$faq->id}}">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq_readonly_{{$faq->id}}"
                                aria-expanded="false" aria-controls="faq_readonly_{{$faq->id}}">
                            {{$faq->question}}
                        </button>
                    </div>
                    <div id="faq_readonly_{{$faq->id}}" class="accordion-collapse collapse"
                         aria-labelledby="faq-readonly-heading-{{$faq->id}}" data-bs-parent="#faqReadonlyAccordionList">
                        <div class="accordion-body">
                            {{$faq->answer}}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
