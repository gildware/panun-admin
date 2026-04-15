<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{translate('invoice')}}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="{{asset('assets/css/bootstrap.min.css')}}" rel="stylesheet" id="bootstrap-css">
    <script src="{{asset('assets/js/bootstrap.min.js')}}"></script>
    <script src="{{asset('assets/js/jquery.min.js')}}"></script>
    <style>
        body {
            background-color: #F9FCFF;
            font-size: 10px !important;
            font-family: "Noto Sans", "DejaVu Sans", "Inter", ui-sans-serif, system-ui, sans-serif;
        }

        a {
            color: rgb(65, 83, 179) !important;
            text-decoration: none !important;
        }

        @media print {
            a {
                text-decoration: none !important;
                -webkit-print-color-adjust: exact;
            }
        }

        #invoice {
            padding: 30px;
        }

        .invoice {
            position: relative;
            min-height: 972px;
            max-width: 972px;
            margin-left: auto;
            margin-right: auto;

        }

        .white-box-content {
            background-color: #FFF;
        }

        .invoice header {
            margin-bottom: 16px;
        }

        .invoice .contacts {
            margin-bottom: 16px
        }

        .invoice .company-details,
        .invoice .invoice-details {
            text-align: right
        }

        .invoice .thanks {
            margin-top: 60px;
            margin-bottom: 30px
        }

        .invoice .footer {
            background-color: rgba(4, 97, 165, 0.05);
        }

        @media print {
            .invoice .notices {
                background-color: #F7F7F7 !important;
                -webkit-print-color-adjust: exact;
            }
        }

        .invoice table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        .invoice table td, .invoice table th {
            padding: 15px;
        }

        .invoice table th {
            white-space: nowrap;
            font-weight: 500;
            background-color: rgba(4, 97, 165, 0.05);
        }

        @media print {
            .invoice table th {
                background-color: rgba(4, 97, 165, 0.05) !important;
                -webkit-print-color-adjust: exact;
            }
        }

        .invoice table tfoot td {
            background: 0 0;
            border: none;
            white-space: nowrap;
            text-align: right;
            padding: 8px 14px;
        }

        .invoice table tfoot tr:first-child td {
            padding-top: 16px;
        }

        .fw-700 {
            font-weight: 700;
        }
        .fs-9 {
            font-size: 9px !important;
        }
        .fs-8 {
            font-size: 8px !important;
        }
        .lh-1 {
            line-height: 1;
        }
        .rounded-12 {
            border-radius: 12px;
        }
        .fz-12 {
            font-size: 12px;
        }
    </style>
</head>
<body>
<div id="invoice">
    <div class="invoice d-flex flex-column">
        <div>
            <header>
                @php($invoice_business_name = business_config('business_name','business_information'))
                <div class="text-center" style="margin-bottom: 12px;">
                    <div class="fw-700" style="font-size: 17px;">{{ $invoice_business_name->live_values ?? '' }}</div>
                </div>
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="text-uppercase fw-700">{{translate("invoice")}}</h3>
                        <div>{{translate('Booking')}} #{{$booking->readable_id}}</div>
                        <div>{{translate('date')}}: {{date('d-M-Y h:ia',strtotime($booking->created_at))}}</div>
                    </div>
                    <div class="col company-details">
                        <a target="_blank" href="#">
                            @php($logo = getBusinessSettingsImageFullPath(key: 'business_logo', settingType: 'business_information', path: 'business/', defaultPath: 'assets/placeholder.png'))
                            <img width="84" height="17" src="{{$logo}}"
                                 data-holder-rendered="true"/>
                        </a>
                        @php($business_email = business_config('business_email','business_information'))
                        @php($business_phone = business_config('business_phone','business_information'))
                        @php($business_address = business_config('business_address','business_information'))
                        <div class="mt-2">{{$business_address->live_values}}</div>
                        <div>{{$business_phone->live_values}}</div>
                        <div>{{$business_email->live_values}}</div>
                    </div>
                </div>
            </header>

            @php($customer_name = $booking->customer ? $booking?->customer?->first_name.' '.$booking?->customer?->last_name : $booking?->service_address?->contact_person_name)
            @php($customer_phone = $booking->customer ? $booking?->customer?->phone : $booking?->service_address?->contact_person_number)

            <div class="white-box-content border rounded-12 border">
                <div class="border-bottom p-3">
                    <div class="row align-items-center justify-content-between">
                        <div class="col">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="fs-9">{{translate('Customer')}}</div>
                                    <div>{{$customer_name}}</div>
                                </div>
                                <div class="col">
                                    <div class="fs-9">{{translate('phone')}}</div>
                                    <div>{{$customer_phone}}</div>
                                </div>
                                <div class="col">
                                    <div class="fs-9">{{translate('email')}}</div>
                                    <div>{{$booking?->customer?->email}}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="text-right">
                                <div>Invoice of ({{currency_code()}})</div>
                                <h5 class="text-primary fw-700 mb-0 lh-1 mt-1">{{with_currency_symbol(get_booking_total_amount_for_display($booking))}}</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3">
                    <div class="row contacts">

                        <div class="col">
                            <div>
                                <div class="fs-9">{{translate('Payment')}}</div>
                                <div class="mt-1">{{ format_booking_payment_method_for_admin_display($booking) }}</div>
                            </div>
                            <div class="mt-3">
                                <div class="fs-9">{{translate('Reference ID')}}</div>
                                <div class="mt-1">{{$booking->readable_id}}</div>
                            </div>
                        </div>

                        <div class="col border-left">
                            <h6 class="fz-12">{{translate('Service Address')}}</h6>
                            <div class="fs-9">
                                @if($booking->service_location == 'provider')
                                    @if($booking->provider_id != null)
                                        @if($booking->provider)
                                            {{ translate('Provider address') }} : {{ $booking->provider->company_address ?? '' }}
                                        @else
                                            {{ translate('Provider Unavailable') }}
                                        @endif
                                    @else
                                        {{ translate('Provider address') }} : {{ translate('The Service Location will be available after this booking accepts or assign to a provider') }}
                                    @endif
                                @else
                                    {{ translate('Customer address') }} : {{$booking?->service_address?->address??translate('not_available')}}
                                @endif
                            </div>

                            <div class="fs-9" style="margin-left: 10px">
                                @if($booking->service_location == 'provider')
                                    #{{ translate('Note') }} : {{ translate('Customer have to go to Service location') }} <b>({{ translate('Provider location') }})</b> {{ translate('in order to receive this service') }}
                                @else
                                    #{{ translate('Note') }} : {{ translate('Provider will be arrived at Service location') }} <b>({{ translate('Customer location') }})</b> {{ translate('to provide the selected services') }}
                                @endif
                            </div>
                        </div>

                        <div class="col border-left">
                            <h6 class="fz-12">{{translate('Service Time')}}</h6>
                            <div class="fs-9">{{translate('Request Date')}} : {{date('d-M-Y h:ia',strtotime($booking->created_at))}}</div>
                            <div class="fs-9">{{translate('Service Date')}} : {{date('d-M-Y h:ia',strtotime($booking->service_schedule))}}</div>
                        </div>
                    </div>


                    <table cellspacing="0" cellpadding="0">
                        <thead>
                            <tr>
                                <th class="text-left">{{translate('SL')}}</th>
                                <th class="text-left text-uppercase">{{translate('description')}}</th>
                                <th class="text-center text-uppercase">{{translate('qty')}}</th>
                                <th class="text-right text-uppercase">{{translate('cost')}}</th>
                                <th class="text-right text-uppercase">{{translate('total')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($sub_total=0; $sl = 0)
                            @foreach($booking->detail as $index=>$item)
                                @php($sl++)
                                <tr>
                                    <td class="border-bottom text-left">{{(strlen($sl)<2?'0':'').$sl}}</td>
                                    <td class="border-bottom text-left">
                                        <div>{{$item->service->name??''}}</div>
                                        <div>{{$item->variant_key}}</div>
                                    </td>
                                    <td class="border-bottom text-center">{{$item->quantity}}</td>
                                    <td class="border-bottom text-right">{{with_currency_symbol($item->service_cost)}}</td>
                                    <td class="border-bottom text-right">{{with_currency_symbol($item->total_cost)}}</td>
                                </tr>
                                @php($sub_total+=$item->service_cost*$item->quantity)
                            @endforeach
                            @php($extraServicesTotal = ($booking->extra_services ?? collect())->sum('total'))
                            @foreach($booking->extra_services ?? [] as $extra)
                                @php($sl++)
                                <tr>
                                    <td class="border-bottom text-left">{{(strlen($sl)<2?'0':'').$sl}}</td>
                                    <td class="border-bottom text-left">
                                        <div>{{ $extra->title }}</div>
                                        @if($extra->details)<div class="text-muted">{{ Str::limit($extra->details, 50) }}</div>@endif
                                        <div class="text-capitalize">{{ $extra->type === 'spare_part' ? translate('Spare_Part') : translate('Service') }}</div>
                                    </td>
                                    <td class="border-bottom text-center">{{ $extra->quantity }}</td>
                                    <td class="border-bottom text-right">{{ with_currency_symbol($extra->price) }}</td>
                                    <td class="border-bottom text-right">{{ with_currency_symbol($extra->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <?php
                            $invDetailSubTotalP = (float) $booking->detail->sum(static fn ($d) => (float) $d->service_cost * (int) $d->quantity);
                            $invExtraServicesSpareTotalP = 0.0;
                            $invExtraServicesServiceTotalP = 0.0;
                            foreach (($booking->extra_services ?? collect()) as $invExtraP) {
                                if (($invExtraP->type ?? '') === \Modules\BookingModule\Entities\BookingExtraService::TYPE_SPARE_PART) {
                                    $invExtraServicesSpareTotalP += (float) $invExtraP->total;
                                } else {
                                    $invExtraServicesServiceTotalP += (float) $invExtraP->total;
                                }
                            }
                            $invServiceAmountExclVatP = $invDetailSubTotalP + $invExtraServicesServiceTotalP;
                            $invBookingHasTaxP = (float) ($booking->total_tax_amount ?? 0) > 0;
                            $invAcDisplayRowsP = enrich_booking_additional_charges_breakdown_for_display($booking);
                            $invGrandTotalP = round(get_booking_total_amount_for_display($booking), 2);
                            $invDisplayServiceDiscountP = round((float) ($booking->total_discount_amount ?? 0) + get_booking_extra_service_line_discount_total($booking), 2);
                            $invSettlementP = get_booking_received_and_settlement($booking);
                            $invDueAmountP = get_booking_invoice_due_amount($booking);
                            $invPaidProviderP = round((float) ($invSettlementP['amount_received_by_provider'] ?? 0), 2);
                            $invPaidCompanyP = round((float) ($invSettlementP['amount_received_by_company'] ?? 0), 2);
                            $invPaidTotalP = round((float) ($invSettlementP['total_paid'] ?? 0), 2);
                        ?>
                        <tfoot>
                            <tr>
                                <td colspan="3"></td>
                                <td class="text-capitalize">{{ translate('service_amount') }}@if($invBookingHasTaxP) <span class="fs-9">{{ booking_tax_excluded_bracket_hint() }}</span>@endif</td>
                                <td class="text-right">{{ with_currency_symbol($invServiceAmountExclVatP) }}</td>
                            </tr>
                            @if($invDisplayServiceDiscountP > 0)
                            <tr>
                                <td colspan="3"></td>
                                <td class="text-capitalize">{{ translate('service_discount') }}</td>
                                <td class="text-right">{{ with_currency_symbol($invDisplayServiceDiscountP) }}</td>
                            </tr>
                            @endif
                            @if((float)($booking->total_coupon_discount_amount ?? 0) > 0)
                            <tr>
                                <td colspan="3"></td>
                                <td class="text-capitalize">{{ translate('coupon_discount') }}</td>
                                <td class="text-right">{{ with_currency_symbol($booking->total_coupon_discount_amount) }}</td>
                            </tr>
                            @endif
                            @if((float)($booking->total_campaign_discount_amount ?? 0) > 0)
                            <tr>
                                <td colspan="3"></td>
                                <td class="text-capitalize">{{ translate('campaign_discount') }}</td>
                                <td class="text-right">{{ with_currency_symbol($booking->total_campaign_discount_amount) }}</td>
                            </tr>
                            @endif
                            @if((float)($booking->total_referral_discount_amount ?? 0) > 0)
                            <tr>
                                <td colspan="3"></td>
                                <td class="text-capitalize">{{ translate('Referral Discount') }}</td>
                                <td class="text-right">{{ with_currency_symbol($booking->total_referral_discount_amount) }}</td>
                            </tr>
                            @endif
                            @if($invBookingHasTaxP)
                            <tr>
                                <td colspan="3"></td>
                                <td>{{ company_default_tax_label() }}</td>
                                <td class="text-right">{{ with_currency_symbol($booking->total_tax_amount) }}</td>
                            </tr>
                            @endif
                            @if ($invExtraServicesSpareTotalP > 0)
                                <tr>
                                    <td colspan="3"></td>
                                    <td class="text-capitalize">{{ translate('Spare_Parts') }}</td>
                                    <td class="text-right">{{ with_currency_symbol($invExtraServicesSpareTotalP) }}</td>
                                </tr>
                            @endif
                            @if ($booking->extra_fee > 0)
                                @if(count($invAcDisplayRowsP))
                                    @foreach($invAcDisplayRowsP as $acRow)
                                    @if((float)($acRow['amount'] ?? 0) > 0)
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="text-capitalize">{{ $acRow['name'] ?? translate('Additional_charges') }}</td>
                                        <td class="text-right">{{ with_currency_symbol($acRow['amount'] ?? 0) }}</td>
                                    </tr>
                                    @endif
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="text-capitalize">{{ translate('Additional_charges') }}</td>
                                        <td class="text-right">{{ with_currency_symbol($booking->extra_fee) }}</td>
                                    </tr>
                                @endif
                            @endif
                            @if($invExtraServicesServiceTotalP > 0)
                                <tr>
                                    <td colspan="3"></td>
                                    <td class="text-capitalize">{{ translate('Extra_Services') }}</td>
                                    <td class="text-right">{{ with_currency_symbol($invExtraServicesServiceTotalP) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="3"></td>
                                <td class="fw-700 border-top">{{ translate('Grand_Total') }}</td>
                                <td class="fw-700 border-top text-right">{{ with_currency_symbol($invGrandTotalP) }}</td>
                            </tr>
                            @if ($booking->booking_partial_payments->isNotEmpty())
                                @if ($invPaidProviderP > 0)
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="fw-700">{{ translate('Paid_to_service_provider') }}</td>
                                        <td class="fw-700 text-right">{{ with_currency_symbol($invPaidProviderP) }}</td>
                                    </tr>
                                @endif
                                @if ($invPaidCompanyP > 0)
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="fw-700">{{ translate('Paid_to_company') }}</td>
                                        <td class="fw-700 text-right">{{ with_currency_symbol($invPaidCompanyP) }}</td>
                                    </tr>
                                @endif
                                @if ($invPaidTotalP > 0)
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="fw-700 border-top">{{ translate('Total_paid') }}</td>
                                        <td class="fw-700 border-top text-right">{{ with_currency_symbol($invPaidTotalP) }}</td>
                                    </tr>
                                @endif
                            @endif

                            @include('bookingmodule::admin.booking.partials._refund-amount-summary-rows', ['booking' => $booking, 'variant' => 'invoice'])

                            @if($invDueAmountP > 0)
                            <tr>
                                <td colspan="3"></td>
                                <td class="fw-700">{{ translate('Due_Amount') }}</td>
                                <td class="fw-700 text-right">{{ with_currency_symbol($invDueAmountP) }}</td>
                            </tr>
                            @endif

                            @if($booking->payment_method != 'cash_after_service' && $booking->additional_charge < 0)
                                <tr>
                                    <td colspan="3"></td>
                                    <td class="fw-700">{{translate('Refund')}}</td>
                                    <td class="fw-700 text-right">{{with_currency_symbol(abs($booking->additional_charge))}}</td>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>

                <div class="mt-5 text-center mb-4">{{translate('Thanks for using our service')}}.</div>
            </div>
        </div>

        <div class="py-4">
            <div class="fw-700">{{translate('Terms & Conditions')}}</div>
            <div>{{translate('Change of mind is not applicable as a reason for refund')}}</div>
        </div>

        <div class="footer p-3">
            <div class="row">
                <div class="col">
                    <div class="text-left">
                        {{Request()->getHttpHost()}}
                    </div>
                </div>
                <div class="col">
                    <div class="text-center">
                        {{$business_phone->live_values}}
                    </div>
                </div>
                <div class="col">
                    <div class="text-right">
                        {{$business_email->live_values}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    "use strict";

    function printContent(el) {
        var restorepage = $('body').html();
        var printcontent = $('#' + el).clone();
        $('body').empty().html(printcontent);
        window.print();
        $('body').html(restorepage);
    }

    printContent('invoice');
</script>
</body>
</html>
