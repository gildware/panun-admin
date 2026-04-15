<?php
$max_booking_amount = (business_config('max_booking_amount', 'booking_setup'))->live_values ?? 0;
$all_bookings_menu_count = \Modules\BookingModule\Entities\Booking::count();
$special_scenarios_menu_count = \Modules\BookingModule\Entities\Booking::query()
    ->where('is_repeated', 0)
    ->whereNotNull('settlement_outcome')
    ->where('settlement_outcome', '!=', '')
    ->count();
$pending_providers = \Modules\ProviderManagement\Entities\Provider::ofApproval(2)->count();
$denied_providers = \Modules\ProviderManagement\Entities\Provider::ofApproval(0)->count();
$logo = getBusinessSettingsImageFullPath(key: 'business_logo', settingType: 'business_information', path: 'business/', defaultPath: 'assets/placeholder.png');
?>

<aside class="aside">
    <div class="aside-header">
        <a href="{{route('admin.dashboard')}}" class="logo d-flex gap-2">
            <img class="main-logo onerror-image" src="{{ $logo }}" alt="{{ translate('image') }}">
        </a>

        <button class="toggle-menu-button aside-toggle border-0 bg-transparent p-0 dark-color">
            <span class="material-icons">menu</span>
        </button>
    </div>


    <div class="aside-body" data-trigger="scrollbar">
        <div class="user-profile media gap-3 align-items-center my-3">
            <div class="avatar">
                <img class="avatar-img rounded-circle aspect-square object-fit-cover" src="{{auth()->user()->profile_image_full_path }}" alt="{{ translate('profile_image') }}">
            </div>
            <div class="media-body ">
                <h5 class="card-title">{{\Illuminate\Support\Str::limit(auth()->user()->email,15)}}</h5>
                <span class="card-text">{{auth()->user()->user_type}}</span>
            </div>
        </div>

        <ul class="nav">
            <li class="nav-category">{{translate('main')}}</li>

            <li>
                <a href="{{route('admin.dashboard')}}" class="{{request()->is('admin/dashboard')?'active-menu':''}}">
                    <span class="material-icons" title="{{translate('dashboard')}}">dashboard</span>
                    <span class="link-title">{{translate('dashboard')}}</span>
                </a>
            </li>

            @canany(['lead_view', 'lead_outbound_enquiry_view', 'lead_configuration_view'])
                <li class="nav-category" title="{{ translate('Lead_Management') }}">
                    {{ translate('Lead_Management') }}
                </li>
                <li class="has-sub-item {{ request()->is('admin/lead*') && !request()->is('admin/lead/reports*') ? 'sub-menu-opened' : '' }}">
                    <a href="#" class="{{ request()->is('admin/lead*') && !request()->is('admin/lead/reports*') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{ translate('Lead_Management') }}">contact_page</span>
                        <span class="link-title">{{ translate('Lead_Management') }}</span>
                    </a>
                    <ul class="nav sub-menu">
                    @can('lead_view')
                        <li>
                            <a href="{{ route('admin.lead.index') }}"
                               class="{{ request()->is('admin/lead') && !request()->is('admin/lead/create') && !request()->is('admin/lead/configuration*') && !request()->is('admin/lead/reports*') ? 'active-menu' : '' }}">
                                <span class="link-title">{{ translate('Leads') }}</span>
                            </a>
                        </li>
                    @endcan
                    @can('lead_outbound_enquiry_view')
                        <li>
                            <a href="{{ route('admin.lead.outbound-enquiry.index') }}"
                               class="{{ request()->is('admin/lead/outbound-enquiry*') ? 'active-menu' : '' }}">
                                <span class="link-title">{{ translate('Outbound_Enquiry') }}</span>
                            </a>
                        </li>
                    @endcan
                    @can('lead_configuration_view')
                        <li>
                            <a href="{{ route('admin.lead.configuration.index') }}"
                               class="{{ request()->is('admin/lead/configuration*') ? 'active-menu' : '' }}">
                                <span class="link-title">{{ translate('Lead_Configuration') }}</span>
                            </a>
                        </li>
                    @endcan
                    </ul>
                </li>
            @endcanany

            @canany(['whatsapp_chat_view', 'whatsapp_message_template_view'])
                <li class="nav-category" title="{{ translate('WhatsApp_and_social_media') }}">
                    {{ translate('WhatsApp_and_social_media') }}
                </li>
                @can('whatsapp_chat_view')
                    <li>
                        <a href="{{ route('admin.whatsapp.conversations.index', ['channel' => 'whatsapp', 'tab' => 'chats']) }}"
                           class="{{ request()->is('admin/social-inbox/whatsapp/*') ? 'active-menu' : '' }}">
                            <span class="material-icons" style="color:#25D366" title="{{ translate('WhatsApp') }}">forum</span>
                            <span class="link-title">{{ translate('WhatsApp') }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.whatsapp.conversations.index', ['channel' => 'instagram', 'tab' => 'chats']) }}"
                           class="{{ request()->is('admin/social-inbox/instagram/*') ? 'active-menu' : '' }}">
                            <span class="material-icons" style="color:#E4405F" title="{{ translate('Instagram') }}">photo_camera</span>
                            <span class="link-title">{{ translate('Instagram') }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.whatsapp.conversations.index', ['channel' => 'facebook', 'tab' => 'chats']) }}"
                           class="{{ request()->is('admin/social-inbox/facebook/*') ? 'active-menu' : '' }}">
                            <span class="material-icons" style="color:#0084FF" title="{{ translate('Facebook_Messenger') }}">chat_bubble</span>
                            <span class="link-title">{{ translate('Facebook') }}</span>
                        </a>
                    </li>
                @endcan
                @can('whatsapp_message_template_view')
                    <li>
                        <a href="{{ route('admin.whatsapp.booking-templates.edit', ['channel' => 'whatsapp']) }}"
                           class="{{ request()->is('admin/social-inbox/*/booking-message-templates*') ? 'active-menu' : '' }}">
                            <span class="material-icons" title="{{ translate('Message_templates') }}">description</span>
                            <span class="link-title">{{ translate('Message_templates') }}</span>
                        </a>
                    </li>
                @endcan
                @can('whatsapp_chat_view')
                    <li>
                        <a href="{{ route('admin.whatsapp.ai-settings.edit', ['channel' => 'whatsapp']) }}"
                           class="{{ request()->is('admin/social-inbox/*/ai-support*') ? 'active-menu' : '' }}">
                            <span class="material-icons" title="{{ __('whatsapp_ai.page_title') }}">smart_toy</span>
                            <span class="link-title">{{ __('whatsapp_ai.page_title') }}</span>
                        </a>
                    </li>
                @endcan
            @endcanany

            @canany(['whatsapp_marketing_template_view', 'whatsapp_marketing_bulk_view', 'whatsapp_marketing_campaign_view', 'whatsapp_marketing_report_view'])
                <li class="nav-category" title="{{ translate('WhatsApp_Marketing') }}">
                    {{ translate('WhatsApp_Marketing') }}
                </li>
                <li class="has-sub-item {{ request()->is('admin/social-inbox/*/marketing*') ? 'sub-menu-opened' : '' }}">
                    <a href="#"
                       class="{{ request()->is('admin/social-inbox/*/marketing*') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{ translate('WhatsApp_Marketing') }}">campaign</span>
                        <span class="link-title">{{ translate('WhatsApp_Marketing') }}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('whatsapp_marketing_bulk_view')
                            <li>
                                <a href="{{ route('admin.whatsapp.marketing.bulk.create', ['channel' => 'whatsapp']) }}"
                                   class="{{ request()->is('admin/social-inbox/*/marketing/send') ? 'active-menu' : '' }}">
                                    <span class="link-title">{{ translate('Send_Bulk_Message') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('whatsapp_marketing_campaign_view')
                            <li>
                                <a href="{{ route('admin.whatsapp.marketing.campaigns.index', ['channel' => 'whatsapp']) }}"
                                   class="{{ request()->is('admin/social-inbox/*/marketing/campaigns*') ? 'active-menu' : '' }}">
                                    <span class="link-title">{{ translate('campaigns') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('whatsapp_marketing_template_view')
                            <li>
                                <a href="{{ route('admin.whatsapp.marketing.templates.index', ['channel' => 'whatsapp']) }}"
                                   class="{{ request()->is('admin/social-inbox/*/marketing/templates*') ? 'active-menu' : '' }}">
                                    <span class="link-title">{{ translate('Templates') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('whatsapp_marketing_report_view')
                            <li>
                                <a href="{{ route('admin.whatsapp.marketing.reports.index', ['channel' => 'whatsapp']) }}"
                                   class="{{ request()->is('admin/social-inbox/*/marketing/reports*') ? 'active-menu' : '' }}">
                                    <span class="link-title">{{ translate('Reports') }}</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            @canany(['booking_view', 'booking_configuration_view'])
                <li class="nav-category" title="{{translate('booking_management')}}">
                    {{translate('booking_management')}}
                </li>
                <li class="has-sub-item {{ request()->is('admin/booking/*') ? 'sub-menu-opened' : '' }}">
                    <a href="#" class="{{ request()->is('admin/booking/*') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="Bookings">calendar_month</span>
                        <span class="link-title">{{translate('bookings')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('booking_configuration_view')
                            <li>
                                <a href="{{ route('admin.booking.configuration.index') }}"
                                   class="{{ request()->is('admin/booking/configuration*') ? 'active-menu' : '' }}">
                                    <span class="link-title">{{ translate('Booking_Configuration') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('booking_view')
                        <li>
                            <a href="{{ route('admin.booking.create') }}"
                               class="{{ request()->is('admin/booking/create') ? 'active-menu' : '' }}">
                                <span class="link-title">{{ translate('Add_New_Booking') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.booking.post.create') }}"
                               class="{{ request()->is('admin/booking/post/create') ? 'active-menu' : '' }}">
                                <span class="link-title">{{ translate('Add_New_Bidding') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.booking.post.list', ['type'=>'all'])}}"
                               class="{{request()->is('admin/booking/post') || request()->is('admin/booking/post/details*') ? 'active-menu' : ''}}">
                                <span class="link-title">{{translate('Customized_Requests')}}
                                    <span
                                        class="count">{{\Modules\BidModule\Entities\Post::where('is_booked', 0)->count()??0}}</span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.booking.list.verification', ['booking_status'=>'pending', 'type' => 'pending'])}}"
                               class="{{request()->is('admin/booking/list/verification') && request()->query('booking_status')=='pending' ?'active-menu':''}}"><span
                                    class="link-title">{{translate('verify_requests')}} <span
                                        class="count">{{\Modules\BookingModule\Entities\Booking::where('is_verified', '0')->where('payment_method', 'cash_after_service')->Where('total_booking_amount', '>', $max_booking_amount)->whereIn('booking_status', ['pending', 'accepted'])->count()}}</span></span></a>
                        </li>
                        <li>
                            <a href="{{ route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']) }}"
                               class="{{ request()->is('admin/booking/list') && ! request()->is('admin/booking/list/verification') && ! request()->is('admin/booking/list/offline-payment') && ! request()->is('admin/booking/list/special-scenarios') ? 'active-menu' : '' }}">
                                <span class="link-title">{{ translate('Booking_Requests') }}
                                    <span class="count">{{ $all_bookings_menu_count }}</span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.booking.list.special_scenarios', ['scenario' => 'all']) }}"
                               class="{{ request()->is('admin/booking/list/special-scenarios') ? 'active-menu' : '' }}">
                                <span class="link-title">{{ translate('Special_scenario_bookings') }}
                                    <span class="count">{{ $special_scenarios_menu_count }}</span>
                                </span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            @canany(['report_view', 'analytics_view', 'lead_report_view'])
                <li class="nav-category" title="{{ translate('Reports & Analytics') }}">
                    {{ translate('Reports & Analytics') }}
                </li>
            @endcanany
            @canany(['report_view', 'lead_report_view'])
                @php
                    $reportsMenuOpen = (request()->is('admin/report/*') && !request()->is('admin/report/transaction*'))
                        || request()->routeIs('admin.lead.reports.index')
                        || request()->routeIs('admin.lead.reports.user');
                @endphp
                <li class="has-sub-item {{ $reportsMenuOpen ? 'sub-menu-opened' : '' }}">
                    <a href="#" class="{{ $reportsMenuOpen ? 'active-menu' : '' }}">
                        <span class="material-icons" title="Customers">event_note</span>
                        <span class="link-title">{{ translate('Reports') }}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('report_view')
                            <li>
                                <a href="{{ route('admin.report.business.overview') }}"
                                   class="{{ request()->is('admin/report/business*') ? 'active-menu' : '' }}">
                                    {{ translate('Business Reports') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.report.booking') }}"
                                   class="{{ request()->is('admin/report/booking') ? 'active-menu' : '' }}">
                                    {{ translate('Booking Reports') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.report.provider') }}"
                                   class="{{ request()->is('admin/report/provider') ? 'active-menu' : '' }}">
                                    {{ translate('Provider Reports') }}
                                </a>
                            </li>
                        @endcan
                        @can('lead_report_view')
                            <li>
                                <a href="{{ route('admin.lead.reports.index', ['tab' => 'inbound']) }}"
                                   class="{{ request()->routeIs('admin.lead.reports.index') ? 'active-menu' : '' }}">
                                    {{ translate('Lead_Reports') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.lead.reports.user', ['user_id' => auth()->id()]) }}"
                                   class="{{ request()->routeIs('admin.lead.reports.user') ? 'active-menu' : '' }}">
                                    {{ translate('User_Report') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany
            @can('analytics_view')
                <li class="has-sub-item {{ request()->is('admin/analytics/*') ? 'sub-menu-opened' : '' }}">
                    <a href="#" class="{{ request()->is('admin/analytics/*') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="Customers">analytics</span>
                        <span class="link-title">{{ translate('Analytics') }}</span>
                    </a>
                    <ul class="nav sub-menu">
                        <li>
                            <a href="{{ route('admin.analytics.search.keyword') }}"
                               class="{{ request()->is('admin/analytics/search/keyword') ? 'active-menu' : '' }}">
                                {{ translate('Keyword_Search') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.analytics.search.customer') }}"
                               class="{{ request()->is('admin/analytics/search/customer') ? 'active-menu' : '' }}">
                                {{ translate('Customer_Search') }}
                            </a>
                        </li>
                    </ul>
                </li>
            @endcan

            @canany(['transaction_view', 'ledger_view', 'report_view'])
                <li class="nav-category" title="{{ translate('transaction_management') }}">
                    {{ translate('transaction_management') }}
                </li>
            @endcanany
            @canany(['transaction_view', 'ledger_view'])
                @can('transaction_view')
                    <li>
                        <a href="{{ route('admin.transaction.list', ['trx_type' => 'all']) }}"
                           class="{{ request()->is('admin/transaction/list') ? 'active-menu' : '' }}">
                            <span class="material-icons" title="Customers">article</span>
                            <span class="link-title">{{ translate('All Transactions') }}</span>
                        </a>
                    </li>
                @endcan
                @can('ledger_view')
                    <li>
                        <a href="{{ route('admin.ledger.index') }}"
                           class="{{ request()->is('admin/ledger') ? 'active-menu' : '' }}">
                            <span class="material-icons" title="{{ translate('Ledger') }}">book</span>
                            <span class="link-title">{{ translate('Ledger') }}</span>
                        </a>
                    </li>
                @endcan
                <li>
                    <a href="{{ route('admin.transaction.pending_provider_balances.index') }}"
                       class="{{ request()->is('admin/transaction/pending-provider-balances') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{ translate('Pending_provider_balances') }}">account_balance_wallet</span>
                        <span class="link-title">{{ translate('Pending_provider_balances') }}</span>
                    </a>
                </li>
            @endcanany
            @can('report_view')
                <li>
                    <a href="{{ route('admin.report.transaction', ['transaction_type' => 'all']) }}"
                       class="{{ request()->is('admin/report/transaction*') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{ translate('Transaction Reports') }}">receipt_long</span>
                        <span class="link-title">{{ translate('Transaction Reports') }}</span>
                    </a>
                </li>
            @endcan

            @canany(['discount_view', 'discount_add', 'coupon_view', 'coupon_add', 'bonus_view', 'bonus_add', 'campaign_view', 'campaign_add','advertisement_view', 'advertisement_add', 'banner_add', 'banner_view' ])
                <li class="nav-category" title="{{translate('promotion_management')}}">
                    {{translate('promotion_management')}}
                </li>
            @endcanany
            @canany(['discount_view', 'discount_add'])
                <li class="has-sub-item {{request()->is('admin/discount/*')?'sub-menu-opened':''}}">
                    <a href="#" class="{{request()->is('admin/discount/*')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('discounts')}}">redeem</span>
                        <span class="link-title">{{translate('discounts')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('discount_view')
                            <li>
                                <a href="{{route('admin.discount.list')}}"
                                   class="{{request()->is('admin/discount/list')?'active-menu':''}}">
                                    {{translate('discount_list')}}
                                </a>
                            </li>
                        @endcan
                        @can('discount_add')
                            <li>
                                <a href="{{route('admin.discount.create')}}"
                                   class="{{request()->is('admin/discount/create')?'active-menu':''}}">
                                    {{translate('add_new_discount')}}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany
            @canany(['coupon_view', 'coupon_add'])
                <li class="has-sub-item {{request()->is('admin/coupon/*')?'sub-menu-opened':''}}">
                    <a href="#" class="{{request()->is('admin/coupon/*')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('coupons')}}">sell</span>
                        <span class="link-title">{{translate('coupons')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('coupon_view')
                            <li>
                                <a href="{{route('admin.coupon.list')}}"
                                   class="{{request()->is('admin/coupon/list')?'active-menu':''}}">
                                    {{translate('coupon_list')}}
                                </a>
                            </li>
                        @endcan
                        @can('coupon_add')
                            <li>
                                <a href="{{route('admin.coupon.create')}}"
                                   class="{{request()->is('admin/coupon/create')?'active-menu':''}}">
                                    {{translate('add_new_coupon')}}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany
            @canany(['bonus_view', 'bonus_add'])
                <li class="has-sub-item {{request()->is('admin/bonus/*')?'sub-menu-opened':''}}">
                    <a href="#" class="{{request()->is('admin/bonus/*')?'active-menu':''}}">
                    <span class="material-icons matarial-symbols-outlined"
                          title="{{translate('bonus')}}">price_change</span>
                        <span class="link-title">{{translate('Wallet Bonus')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('bonus_view')
                            <li>
                                <a href="{{route('admin.bonus.list')}}"
                                   class="{{request()->is('admin/bonus/list')?'active-menu':''}}">
                                    {{translate('bonus_list')}}
                                </a>
                            </li>
                        @endcan
                        @can('bonus_add')
                            <li>
                                <a href="{{route('admin.bonus.create')}}"
                                   class="{{request()->is('admin/bonus/create')?'active-menu':''}}">
                                    {{translate('add_new_bonus')}}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany
            @canany(['campaign_view', 'campaign_add'])
                <li class="has-sub-item {{request()->is('admin/campaign/*')?'sub-menu-opened':''}}">
                    <a href="#" class="{{request()->is('admin/campaign/*')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('campaigns')}}">campaign</span>
                        <span class="link-title">{{translate('campaigns')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('campaign_view')
                            <li>
                                <a href="{{route('admin.campaign.list')}}"
                                   class="{{request()->is('admin/campaign/list')?'active-menu':''}}">
                                    {{translate('campaign_list')}}
                                </a>
                            </li>
                        @endcan
                        @can('campaign_add')
                            <li>
                                <a href="{{route('admin.campaign.create')}}"
                                   class="{{request()->is('admin/campaign/create')?'active-menu':''}}">
                                    {{translate('add_new_campaign')}}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany
            @canany(['advertisement_view', 'advertisement_add'])
                <li class="has-sub-item {{request()->is('admin/advertisements/*')?'sub-menu-opened':''}}">
                    <a href="#" class="{{request()->is('admin/advertisements/*')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('advertisements')}}">campaign</span>
                        <span class="link-title">{{translate('advertisements')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('advertisement_view')
                            <li>
                                <a href="{{route('admin.advertisements.ads-list', ['status' => 'all'])}}"
                                   class="{{request()->is('admin/advertisements/ads-list')?'active-menu':''}}">
                                    {{translate('Ads List')}}
                                </a>
                            </li>
                        @endcan
                        @can('advertisement_add')
                            <li>
                                <a href="{{route('admin.advertisements.new-ads-request', ['status' => 'new'])}}"
                                   class="{{request()->is('admin/advertisements/new-ads-request')?'active-menu':''}}">
                                    {{translate('New Ads Request')}}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany
            @canany(['banner_add', 'banner_view'])
                <li>
                    <a href="{{route('admin.banner.create')}}"
                       class="{{request()->is('admin/banner/*')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('promotional_banners')}}">flag</span>
                        <span class="link-title">{{translate('promotional_banners')}}</span>
                    </a>
                </li>
            @endcanany

            @canany(['push_notification_view','push_notification_add', 'notification_message_view', 'notification_message_add', 'notification_message_update', 'notification_channel_view', 'notification_channel_add' ])
                <li class="nav-category" title="{{translate('notification_management')}}">
                    {{translate('notification_management')}}
                </li>
            @endcanany
            @canany(['push_notification_add', 'push_notification_view'])
                <li>
                    <a href="{{route('admin.push-notification.create')}}" class="{{request()->is('admin/push-notification/*')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('push_notification')}}">send</span>
                        <span class="link-title">{{translate('Send Notifications')}}</span>
                    </a>
                </li>
            @endcanany

            @canany(['notification_message_view', 'notification_message_add', 'notification_message_update'])
                <li>
                    <a href="{{route('admin.configuration.get-notification-setting', ['type' => 'customers'])}}"
                       class="{{request()->is('admin/configuration/get-notification-setting')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('push_notification')}}">notifications</span>
                        <span class="link-title"> {{translate('Push Notification')}}</span>
                    </a>
                </li>
            @endcanany
            @canany(['notification_channel_view', 'notification_channel_add'])
                <li>
                    <a href="{{route('admin.business-settings.notification-channel', ['notification_type' => 'user'])}}" class="{{request()->is('admin/business-settings/notification-channel') ?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('push_notification')}}">notifications_active</span>
                        <span class="link-title"> {{translate('Notification Channel')}}</span>

                    </a>
                </li>
            @endcanany


            @canany(['provider_view', 'provider_add', 'onboarding_request_view','withdraw_view', 'withdraw_add'])
                <li class="nav-category"
                    title="{{translate('provider_management')}}">
                    {{translate('provider_management')}}
                </li>
            @endcanany
            @can('onboarding_request_view')
                <li>
                    <a href="{{route('admin.provider.onboarding_request', ['status'=>'onboarding'])}}"
                       class="{{request()->is('admin/provider/onboarding*')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('Onboarding_Request')}}">description</span>
                        <span class="link-title">{{translate('Onboarding_Request')}} <span
                                class="count">{{$pending_providers + $denied_providers}}</span></span>
                    </a>
                </li>
            @endcan
            @canany(['provider_view', 'provider_add'])
                <li class="has-sub-item  {{(request()->is('admin/provider/list') || request()->is('admin/provider/create') || request()->is('admin/provider/details*') || request()->is('admin/provider/edit*') || request()->is('admin/provider/collect-cash*'))?'sub-menu-opened':''}}">
                    <a href="#"
                       class="{{(request()->is('admin/provider/list') || request()->is('admin/provider/create') || request()->is('admin/provider/details*') || request()->is('admin/provider/edit*') || request()->is('admin/provider/collect-cash*'))?'active-menu':''}}">
                        <span class="material-icons" title="Providers">engineering</span>
                        <span class="link-title">{{translate('providers')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('provider_view')
                            <li>
                                <a href="{{route('admin.provider.list', ['status'=>'all'])}}"
                                   class="{{(request()->is('admin/provider/list'))?'active-menu':''}}">{{translate('Provider_List')}}</a>
                            </li>
                        @endcan
                        @can('provider_add')
                            <li><a href="{{route('admin.provider.create')}}"
                                   class="{{(request()->is('admin/provider/create'))?'active-menu':''}}">{{translate('Add_New_Provider')}}</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan
            @can('provider_view')
                <li>
                    <a href="{{ route('admin.provider.feedback-tags.index') }}"
                       class="{{ request()->is('admin/provider/feedback-tags*') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{ translate('Feedback_Configuration') }}">tune</span>
                        <span class="link-title">{{ translate('Feedback_Configuration') }}</span>
                    </a>
                </li>
            @endcan
            @canany(['withdraw_view', 'withdraw_add'])
                <li class="has-sub-item  {{request()->is('admin/withdraw/method*')||request()->is('admin/withdraw/method/create')||request()->is('admin/withdraw/method/edit*') || request()->is('admin/withdraw/request*') ?'sub-menu-opened':''}}">
                    <a href="#"
                       class="{{request()->is('admin/withdraw/method*')||request()->is('admin/withdraw/method/create')||request()->is('admin/withdraw/method/edit*') || request()->is('admin/withdraw/request*') ?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('withdraw_methods')}}">payments</span>
                        <span class="link-title">{{translate('Withdraws')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('withdraw_view')
                            <li>
                                <a href="{{route('admin.withdraw.request.list', ['status'=>'all'])}}"
                                   class="{{request()->is('admin/withdraw/request*')?'active-menu':''}}">
                                    {{translate('Withdraw Requests')}}
                                </a>
                            </li>
                        @endcan
                        @can('withdraw_add')
                            <li>
                                <a href="{{route('admin.withdraw.method.list')}}"
                                   class="{{request()->is('admin/withdraw/method*')||request()->is('admin/withdraw/method/create')||request()->is('admin/withdraw/method/edit*')?'active-menu':''}}">
                                    {{translate('Withdraw method setup')}}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            @canany(['service_view','service_add','zone_add', 'zone_view', 'category_view', 'category_add'])
                <li class="nav-category" title="{{translate('service_management')}}">
                    {{translate('service_management')}}
                </li>
            @endcanany
            @canany(['zone_add', 'zone_view'])
                <li>
                    <a href="{{route('admin.zone.create')}}"
                       class="{{request()->is('admin/zone/*')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('service_zones')}}">map</span>
                        <span class="link-title">{{translate('Service Zones Setup')}}</span>
                    </a>
                </li>
            @endcanany
            @canany(['category_add', 'category_view'])
                <li class="has-sub-item {{(request()->is('admin/category/*') || request()->is('admin/sub-category/*'))?'sub-menu-opened':''}}">
                    <a href="#"
                       class="{{(request()->is('admin/category/*') || request()->is('admin/sub-category/*'))?'active-menu':''}}">
                        <span class="material-icons" title="Service Categories">category</span>
                        <span class="link-title">{{translate('Categories')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        <li>
                            <a href="{{route('admin.category.create')}}"
                               class="{{request()->is('admin/category/*')?'active-menu':''}}">
                                {{translate('Category Setup')}}
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.sub-category.create')}}"
                               class="{{request()->is('admin/sub-category/*')?'active-menu':''}}">
                                {{translate('Sub Category Setup')}}
                            </a>
                        </li>
                    </ul>
                </li>
            @endcanany
            @canany(['service_view','service_add'])
                <li class="has-sub-item {{request()->is('admin/service/*')?'sub-menu-opened':''}}">
                    <a href="#" class="{{request()->is('admin/service/*')?'active-menu':''}}">
                        <span class="material-icons" title="Services">design_services</span>
                        <span class="link-title">{{translate('services')}}</span>
                    </a>
                    <ul class="nav flex-column sub-menu">
                        @can('service_view')
                            <li>
                                <a href="{{route('admin.service.index')}}"
                                   class="{{request()->is('admin/service/list')?'active-menu':''}}">
                                    {{translate('service_list')}}
                                </a>
                            </li>
                        @endcan
                        @can('service_add')
                            <li>
                                <a href="{{route('admin.service.create')}}"
                                   class="{{request()->is('admin/service/create')?'active-menu':''}}">
                                    {{translate('add_new_service')}}
                                </a>
                            </li>
                        @endcan
                        @can('service_view')
                            <li>
                                <a href="{{route('admin.service.request.list')}}"
                                   class="{{request()->is('admin/service/request/list*')?'active-menu':''}}">
                                    <span class="link-title">{{translate('New Service Requests')}}</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            @canany(['wallet_add','wallet_view','customer_view','customer_add','point_view', 'newsletter_view'])
                <li class="nav-category" title="{{translate('customer_management')}}">
                    {{translate('customer_management')}}
                </li>
            @endcanany

            @canany(['customer_view','customer_add'])
                <li class="has-sub-item {{request()->is('admin/customer/list')||request()->is('admin/customer/create') ?'sub-menu-opened':''}}">
                    <a href="#"
                       class="{{request()->is('admin/customer/list') || request()->is('admin/customer/detail*') || request()->is('admin/customer/edit/*') ||request()->is('admin/customer/create')?'active-menu':''}}">
                        <span class="material-icons" title="Customers">person_outline</span>
                        <span class="link-title">{{translate('customers')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('customer_view')
                            <li>
                                <a href="{{route('admin.customer.index')}}"
                                   class="{{request()->is('admin/customer/list')?'active-menu':''}}">
                                    {{translate('customer_list')}}
                                </a>
                            </li>
                        @endcan
                        @can('customer_add')
                            <li>
                                <a href="{{route('admin.customer.create')}}"
                                   class="{{request()->is('admin/customer/create')?'active-menu':''}}">
                                    {{translate('add_new_customer')}}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            @canany(['wallet_add','wallet_view'])
                <li class="has-sub-item {{request()->is('admin/customer/wallet*')?'sub-menu-opened':''}}">
                    <a href="#" class="{{request()->is('admin/customer/wallet*')?'active-menu':''}}">
                        <span class="material-icons" title="Customers">wallet</span>
                        <span class="link-title">{{translate('customer_wallet')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('wallet_add')
                            <li>
                                <a href="{{route('admin.customer.wallet.add-fund')}}"
                                   class="{{request()->is('admin/customer/wallet/add-fund')?'active-menu':''}}">
                                    {{translate('Add Fund to Wallet')}}
                                </a>
                            </li>
                        @endcan
                        @can('wallet_view')
                            <li>
                                <a href="{{route('admin.customer.wallet.report')}}"
                                   class="{{request()->is('admin/customer/wallet/report')?'active-menu':''}}">
                                    {{translate('Wallet Transactions')}}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            @can('point_view')
                <li class="has-sub-item {{request()->is('admin/customer/loyalty-point*')?'sub-menu-opened':''}}">
                    <a href="#" class="{{request()->is('admin/customer/loyalty-point*')?'active-menu':''}}">
                        <span class="material-icons" title="Customers">paid</span>
                        <span class="link-title">{{translate('loyalty_point')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        <li>
                            <a href="{{route('admin.customer.loyalty-point.report')}}"
                               class="{{request()->is('admin/customer/loyalty-point/report')?'active-menu':''}}">
                                {{translate('Loyalty Points Transactions')}}
                            </a>
                        </li>
                    </ul>
                </li>
            @endcan

            @can('newsletter_view')
                <li>
                    <a href="{{route('admin.customer.newsletter.index')}}"
                       class="{{request()->is('admin/customer/newsletter/*')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('subscribed_newsletter')}}">email</span>
                        <span class="link-title">{{translate('Subscribed Newsletter')}}</span>
                    </a>
                </li>
            @endcan

            @canany(['role_view', 'role_add', 'employee_add', 'employee_view'])
                <li class="nav-category" title="{{translate('employee_management')}}">{{translate('employee_management')}}</li>
            @endcanany

            @canany(['role_view', 'role_add'])
                <li>
                    <a href="{{route('admin.role.index')}}" class="{{request()->is('admin/role/*')?'active-menu':''}}">
                        <span class="material-icons" title="Employee">settings</span>
                        <span class="link-title">{{translate('Employee Role Setup')}}</span>
                    </a>
                </li>
            @endcan
            @can('employee_view')
                <li>
                    <a href="{{route('admin.employee.index')}}"
                       class="{{request()->is('admin/employee/list') ||  request()->is('admin/employee/edit/*') ? 'active-menu':''}}">
                        <span class="material-icons" title="{{translate('employee_list')}}">list</span>
                        <span class="link-title">{{translate('employee_list')}}</span>
                    </a>
                </li>
            @endcan
            @can('employee_add')
                <li>
                    <a href="{{route('admin.employee.create')}}"
                       class="{{request()->is('admin/employee/create')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('add_new_employee')}}">add</span>
                        <span class="link-title">{{translate('add_new_employee')}}</span>
                    </a>
                </li>
            @endcan

            @canany(['business_view', 'subscription_package_view', 'subscriber_view', 'subscription_settings_view', 'page_view', 'landing_view', 'error_logs_view', 'cron_job_view'])
                <li class="nav-category" title="{{translate('business_setup')}}">{{translate('business_setup')}}</li>
            @endcanany

            @can('business_view')
                <li>
                    <a href="{{route('admin.business-settings.get-business-information')}}"
                       class="{{request()->is('admin/business-settings/get-business-information')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('push_notification')}}">settings</span>
                        <span class="link-title"> {{translate('business_Settings')}}</span>
                    </a>
                </li>
            @endcan

            @canany(['subscription_settings_view', 'subscriber_view', 'subscription_package_view'])
                <li class="has-sub-item {{request()->is('admin/subscription/*')?'sub-menu-opened':''}}">
                    <a href="#" class="{{request()->is('admin/subscription/*')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('Subscription Management')}}">campaign</span>
                        <span class="link-title">{{translate('Subscription Management')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('subscription_package_view')
                            <li>
                                <a href="{{route('admin.subscription.package.list')}}"
                                   class="{{request()->is('admin/subscription/package/*')?'active-menu':''}}">
                                    {{translate('Subscription Package')}}
                                </a>
                            </li>
                        @endcan
                        @can('subscriber_view')
                            <li>
                                <a href="{{route('admin.subscription.subscriber.list')}}"
                                   class="{{request()->is('admin/subscription/subscriber/*') ?'active-menu':''}}">
                                    {{translate('Subscriber List')}}
                                </a>
                            </li>
                        @endcan
                        @can('subscription_settings_view')
                            <li>
                                <a href="{{route('admin.subscription.settings')}}"
                                   class="{{request()->is('admin/subscription/settings') ?'active-menu':''}}">
                                    {{translate('Settings')}}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            @canany(['page_view', 'landing_view'])
                <li class="has-sub-item {{request()->is('admin/business-page-setup/*') || request()->is('admin/social-media/*') || request()->is('admin/business-settings/get-landing-information*') ? 'sub-menu-opened':''}}">
                    <a href="#"
                       class="{{request()->is('admin/business-page-setup/*') || request()->is('admin/social-media/*') || request()->is('admin/business-settings/get-landing-information*') ?'active-menu':''}}">
                        <span class="material-icons" title="Business pages">article</span>
                        <span class="link-title">{{translate('Page & Media')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @can('page_view')
                            <li>
                                <a href="{{route('admin.business-page-setup.list')}}"
                                   class="{{request()->is('admin/business-page-setup*')?'active-menu':''}}">
                                    {{translate('Business Pages')}}
                                </a>
                            </li>
                        @endcan
                            @can('page_view')
                                <li>
                                    <a href="{{ route('admin.social-media.index') }}"
                                       class="{{request()->is('admin/social-media/*')?'active-menu':''}}">
                                        {{translate('Social Media')}}
                                    </a>
                                </li>
                            @endcan

                        @can('landing_view')
                            <li>
                                <a href="{{route('admin.business-settings.get-landing-information', ['web_page' => 'text_setup'])}}"
                                   class="{{request()->is('admin/business-settings/get-landing-information')?'active-menu':''}}">
                                    <span class="link-title">{{translate('landing_page_settings')}}</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            @can('error_logs_view')
                <li>
                    <a href="{{route('admin.business-settings.seo.setting', ['page_type' => 'error_logs'])}}"
                       class="{{request()->is('admin/business-settings/seo-setting') ?'active-menu':''}}">
                        <span class="material-icons" title="Business 404 Logs">error</span>
                        <span class="link-title">{{translate('404 Logs')}}</span>
                    </a>
                </li>
            @endcan

            @can('cron_job_view')
                <li>
                    <a href="{{route('admin.business-settings.cron-job.list')}}"
                       class="{{request()->is('admin/business-settings/cron-job') ?'active-menu':''}}">
                        <span class="material-icons" title="Cron Job">work</span>
                        <span class="link-title">{{translate('Cron Job')}}</span>
                    </a>
                </li>
            @endcan


            @canany(['login_setup_view', 'language_view', 'gallery_view', 'backup_view', 'service_view', 'category_view', 'customer_view', 'provider_view', 'lead_view', 'booking_view', 'business_view'])
                <li class="nav-category" title="{{translate('system_setup')}}">{{translate('system_setup')}}</li>
            @endcanany

            @can('login_setup_view')
                <li>
                    <a href="{{route('admin.business-settings.login.setup')}}"
                       class="{{request()->is('admin/business-settings/login/setup') ?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('Login Setup')}}">login</span>
                        <span class="link-title">{{translate('Login Setup')}}</span>
                    </a>
                </li>
            @endcan

            @can('language_view')
                <li>
                    <a href="{{route('admin.configuration.language_setup')}}"
                       class="{{request()->is('admin/configuration/language-setup') || request()->is('admin/language/translate/*') ?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('Language Setup')}}">language</span>
                        <span class="link-title">{{translate('Language Setup')}}</span>
                    </a>
                </li>
            @endcan

            @can('gallery_view')
                <li>
                    <a href="{{route('admin.business-settings.get-gallery-setup')}}"
                       class="{{request()->is('admin/business-settings/get-gallery-setup*')?'active-menu':''}}">
                        <span class="material-icons" title="Page Settings">collections_bookmark</span>
                        <span class="link-title">{{translate('Gallery')}}</span>
                    </a>
                </li>
            @endcan
            @can('backup_view')
                <li>
                    <a href="{{route('admin.business-settings.get-database-backup')}}"
                       class="{{request()->is('admin/business-settings/get-database-backup')?'active-menu':''}}">
                        <span class="material-icons" title="Page Settings">backup</span>
                        <span class="link-title">{{translate('Backup_Database')}}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.system-maintenance.data-reset.index') }}"
                       class="{{ request()->is('admin/system-maintenance/data-reset') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{ translate('Reset_Operational_Data') }}">delete_forever</span>
                        <span class="link-title">{{ translate('Reset_Operational_Data') }}</span>
                    </a>
                </li>
            @endcan

            @canany(['service_view', 'category_view', 'customer_view', 'provider_view', 'lead_view', 'booking_view', 'business_view'])
                <li>
                    <a href="{{ route('admin.data-transfer.index') }}"
                       class="{{ request()->is('admin/data-transfer*') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{ translate('Data_Transfer') }}">import_export</span>
                        <span class="link-title">{{ translate('Data_Transfer') }}</span>
                    </a>
                </li>
            @endcanany

            @canany(['firebase_view', 'payment_method_view', 'configuration_view', 'ai_configuration_view'])
                <li class="nav-category" title="{{translate('3rd_party_setup')}}">{{translate('3rd Party Setup')}}</li>
            @endcanany

            @can('firebase_view')
                <li>
                    <a href="{{ route('admin.configuration.third-party', 'firebase-configuration') }}"
                       class="{{ request()->is('admin/configuration/third-party/firebase-*') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{ translate('push_notification') }}">notifications</span>
                        <span class="link-title">{{ translate('Firebase') }}</span>
                    </a>
                </li>
            @endcan
            @can('payment_method_view')
                <li>
                    <a href="{{ route('admin.configuration.third-party', ['webPage' => 'payment_config', 'type' => 'digital_payment']) }}"
                       class="{{ request()->is('admin/configuration/third-party/payment_config*') || request()->is('admin/configuration/offline*') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{ translate('Payment Methods') }}">payment</span>
                        <span class="link-title">{{ translate('Payment Methods') }}</span>
                    </a>
                </li>
            @endcan
            @can('ai_configuration_view')
                <li>
                    <a href="{{ route('admin.configuration.ai-configuration') }}"
                       class="{{ request()->is('admin/configuration/ai-configuration') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{ translate('AI_Configuration') }}">auto_awesome</span>
                        <span class="link-title">{{ translate('AI_Configuration') }}</span>
                    </a>
                </li>
            @endcan
            @can('configuration_view')
                <li>
                    <a href="{{ route('admin.configuration.third-party', 'map-api') }}"
                       class="{{ (request()->is('admin/configuration/third-party/*') ||  request()->is('admin/configuration/ai-settings/*'))
                        && !request()->is('admin/configuration/third-party/firebase-*')
                        && !request()->is('admin/configuration/third-party/payment_config*') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{ translate('Other Configuration') }}">settings</span>
                        <span class="link-title">{{ translate('Other Configuration') }}</span>
                    </a>
                </li>
            @endcan

            @canany(['addon_view', 'addon_add'])
                <li class="nav-category" title="{{translate('system_addon')}}">
                    {{translate('system_addon')}}
                </li>
                <li>
                    <a class="{{Request::is('admin/addon')?'active-menu':''}}"
                       href="{{route('admin.addon.index')}}" title="{{translate('system_addons')}}">
                        <span class="material-icons" title="add_circle_outline">add_circle_outline</span>
                        <span class="link-title">{{translate('system_addons')}}</span>
                    </a>
                </li>

                @if(count(config('addon_admin_routes'))>0)
                    <li class="has-sub-item {{request()->is('admin/payment/configuration/*') || request()->is('admin/sms/configuration/*')?'sub-menu-opened':''}}">
                        <a href="#"
                           class="{{request()->is('admin/payment/configuration/*') || request()->is('admin/sms/configuration/*')?'active-menu':''}}">
                            <span class="material-symbols-outlined">list</span>
                            <span class="link-title">{{translate('addon_menus')}}</span>
                        </a>
                        <ul class="nav flex-column sub-menu">
                            @foreach(config('addon_admin_routes') as $routes)
                                @foreach($routes as $route)
                                    <li>
                                        <a class="{{ Request::is($route['path']) ?'active-menu':'' }}"
                                           href="{{ $route['url'] }}" title="{{ translate($route['name']) }}">
                                            {{ translate($route['name']) }}
                                        </a>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </li>
                @endif
            @endcanany


            @canany(['addon_view', 'addon_update'])
                <li>
                    <a href="{{route('admin.add-on-activation.index')}}"
                       class="{{request()->is('admin/add-on-activation/index') ?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('Add-on Activation')}}">add_card</span>
                        <span class="link-title">{{translate('Add-on Activation')}}</span>
                    </a>
                </li>
            @endcanany

        </ul>
    </div>
</aside>
