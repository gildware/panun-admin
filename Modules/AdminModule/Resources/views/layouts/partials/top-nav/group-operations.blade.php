@canany(['lead_view', 'lead_outbound_enquiry_view', 'lead_configuration_view', 'whatsapp_chat_view', 'whatsapp_message_template_view', 'whatsapp_marketing_template_view', 'whatsapp_marketing_bulk_view', 'whatsapp_marketing_campaign_view', 'whatsapp_marketing_report_view', 'booking_view', 'booking_configuration_view'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('operations'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        {{ translate('Operations') }} <span class="material-icons">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @canany(['lead_view', 'lead_outbound_enquiry_view', 'lead_configuration_view'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('Lead_Management')])
            @can('lead_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.lead.index'),
                    'label' => translate('Leads'),
                    'active' => (request()->is('admin/lead') || request()->is('admin/lead/*')) && !request()->is('admin/lead/create*') && !request()->is('admin/lead/configuration*') && !request()->is('admin/lead/reports*') && !request()->is('admin/lead/outbound-enquiry*'),
                ])
            @endcan
            @can('lead_outbound_enquiry_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.lead.outbound-enquiry.index'),
                    'label' => translate('Outbound_Enquiry'),
                    'active' => request()->is('admin/lead/outbound-enquiry*'),
                ])
            @endcan
            @can('lead_configuration_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.lead.configuration.index'),
                    'label' => translate('Lead_Configuration'),
                    'active' => request()->is('admin/lead/configuration*'),
                ])
            @endcan
        @endcanany

        @can('lead_outbound_enquiry_view')
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('Voice')])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.voice-call.index'),
                'label' => translate('Voice_Calls'),
                'active' => request()->is('admin/voice-call*'),
            ])
        @endcan

        @canany(['whatsapp_chat_view', 'whatsapp_message_template_view'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('WhatsApp_and_social_media')])
            @can('whatsapp_chat_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.whatsapp.conversations.index', ['channel' => 'whatsapp', 'tab' => 'chats']),
                    'label' => translate('WhatsApp'),
                    'active' => request()->is('admin/social-inbox/whatsapp/*'),
                ])
            @endcan
            @can('whatsapp_message_template_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.whatsapp.booking-templates.edit', ['channel' => 'whatsapp']),
                    'label' => translate('Message_templates'),
                    'active' => request()->is('admin/social-inbox/*/booking-message-templates*'),
                ])
            @endcan
            @can('whatsapp_chat_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.whatsapp.ai-settings.edit', ['channel' => 'whatsapp']),
                    'label' => __('whatsapp_ai.page_title'),
                    'active' => request()->is('admin/social-inbox/*/ai-support*'),
                ])
            @endcan
        @endcanany

        @canany(['whatsapp_marketing_template_view', 'whatsapp_marketing_bulk_view', 'whatsapp_marketing_campaign_view', 'whatsapp_marketing_report_view'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('WhatsApp_Marketing')])
            @can('whatsapp_marketing_bulk_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.whatsapp.marketing.bulk.create', ['channel' => 'whatsapp']),
                    'label' => translate('Send_Bulk_Message'),
                    'active' => request()->is('admin/social-inbox/*/marketing/send'),
                ])
            @endcan
            @can('whatsapp_marketing_campaign_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.whatsapp.marketing.campaigns.index', ['channel' => 'whatsapp']),
                    'label' => translate('campaigns'),
                    'active' => request()->is('admin/social-inbox/*/marketing/campaigns*'),
                ])
            @endcan
            @can('whatsapp_marketing_template_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.whatsapp.marketing.templates.index', ['channel' => 'whatsapp']),
                    'label' => translate('Templates'),
                    'active' => request()->is('admin/social-inbox/*/marketing/templates*'),
                ])
            @endcan
            @can('whatsapp_marketing_report_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.whatsapp.marketing.reports.index', ['channel' => 'whatsapp']),
                    'label' => translate('Reports'),
                    'active' => request()->is('admin/social-inbox/*/marketing/reports*'),
                ])
            @endcan
        @endcanany

        @canany(['booking_view', 'booking_configuration_view'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('booking_management')])
            @can('booking_configuration_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.booking.configuration.index'),
                    'label' => translate('Booking_Configuration'),
                    'active' => request()->is('admin/booking/configuration*'),
                ])
            @endcan
            @can('booking_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.booking.create'),
                    'label' => translate('Add_New_Booking'),
                    'active' => request()->is('admin/booking/create'),
                ])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.booking.post.create'),
                    'label' => translate('Add_New_Bidding'),
                    'active' => request()->is('admin/booking/post/create'),
                ])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.booking.post.list', ['type' => 'all']),
                    'label' => translate('Customized_Requests'),
                    'active' => request()->is('admin/booking/post') || request()->is('admin/booking/post/details*'),
                    'count' => \Modules\BidModule\Entities\Post::where('is_booked', 0)->count() ?? 0,
                ])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.booking.list.verification', ['booking_status' => 'pending', 'type' => 'pending']),
                    'label' => translate('verify_requests'),
                    'active' => request()->is('admin/booking/list/verification') && request()->query('booking_status') == 'pending',
                    'count' => \Modules\BookingModule\Entities\Booking::where('is_verified', '0')->where('payment_method', 'cash_after_service')->where('total_booking_amount', '>', (float) $max_booking_amount)->whereIn('booking_status', ['pending', 'accepted'])->count(),
                ])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']),
                    'label' => translate('Booking_Requests'),
                    'active' => (request()->is('admin/booking/list') || request()->is('admin/booking/details*') || request()->is('admin/booking/repeat*') || request()->is('admin/booking/rebooking*') || request()->is('admin/booking/todays-followups*') || request()->is('admin/booking/success*')) && !request()->is('admin/booking/list/verification') && !request()->is('admin/booking/list/offline-payment') && !request()->is('admin/booking/list/special-scenarios') && !request()->is('admin/booking/list/cancelled-by-provider') && !request()->is('admin/booking/reviews/list'),
                    'count' => $all_bookings_menu_count,
                ])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.booking.list.cancelled_by_provider', ['service_type' => 'all']),
                    'label' => translate('Cancelled_by_provider'),
                    'active' => request()->is('admin/booking/list/cancelled-by-provider'),
                    'count' => $menuCounts['cancelled_by_provider'] ?? 0,
                ])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.booking.list.special_scenarios', ['scenario' => 'all']),
                    'label' => translate('Special_scenario_bookings'),
                    'active' => request()->is('admin/booking/list/special-scenarios'),
                    'count' => $special_scenarios_menu_count,
                ])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.booking.reviews.list'),
                    'label' => translate('Booking_Review'),
                    'active' => request()->is('admin/booking/reviews/list'),
                    'count' => $pending_booking_reviews_count,
                ])
            @endcan
        @endcanany
    </div>
</div>
@endcanany
