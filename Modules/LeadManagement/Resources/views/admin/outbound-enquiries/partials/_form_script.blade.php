@push('script')
    <script>
        "use strict";
        (function ($) {
            const leadSearchUrl = @json(route('admin.lead.outbound-enquiry.search-leads'));
            const bookingSearchUrl = @json(route('admin.lead.outbound-enquiry.search-bookings'));
            const LINK_LEAD = 'lead';
            const LINK_BOOKING = 'booking';

            function initOutboundEnquiryLeadSelect($select) {
                if (!$select.length || $select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                const selected = $select.data('selected') || null;

                $select.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $select.data('placeholder') || '',
                    ajax: {
                        url: leadSearchUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term || '', selected: selected };
                        },
                        processResults: function (data) {
                            return data;
                        },
                        cache: true,
                    },
                    minimumInputLength: 0,
                });

                if (selected) {
                    $.get(leadSearchUrl, { selected: selected }, function (data) {
                        const match = (data.results || []).find(function (item) {
                            return String(item.id) === String(selected);
                        });
                        if (match) {
                            const option = new Option(match.text, match.id, true, true);
                            $select.append(option).trigger('change');
                        }
                    });
                }
            }

            function initOutboundEnquiryBookingSelect($select) {
                if (!$select.length || $select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                const selected = $select.data('selected') || null;

                $select.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $select.data('placeholder') || '',
                    ajax: {
                        url: bookingSearchUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term || '', selected: selected };
                        },
                        processResults: function (data) {
                            return data;
                        },
                        cache: true,
                    },
                    minimumInputLength: 0,
                });

                if (selected) {
                    $.get(bookingSearchUrl, { selected: selected }, function (data) {
                        const match = (data.results || []).find(function (item) {
                            return String(item.id) === String(selected);
                        });
                        if (match) {
                            const option = new Option(match.text, match.id, true, true);
                            $select.append(option).trigger('change');
                        }
                    });
                }
            }

            function applyOutboundEnquiryStatusLinks($scope) {
                const $status = $scope.find('.outbound-enquiry-status-select');
                const linkType = $status.find('option:selected').data('link-type') || '';
                const $leadWrap = $scope.find('.outbound-enquiry-lead-link-wrap');
                const $bookingWrap = $scope.find('.outbound-enquiry-booking-link-wrap');
                const $leadSelect = $scope.find('.outbound-enquiry-lead-select');
                const $bookingSelect = $scope.find('.outbound-enquiry-booking-select');

                const showLead = linkType === LINK_LEAD;
                const showBooking = linkType === LINK_BOOKING;

                $leadWrap.toggleClass('d-none', !showLead);
                $bookingWrap.toggleClass('d-none', !showBooking);

                $leadSelect.prop('required', showLead);
                $bookingSelect.prop('required', showBooking);

                if (showLead) {
                    initOutboundEnquiryLeadSelect($leadSelect);
                }
                if (showBooking) {
                    initOutboundEnquiryBookingSelect($bookingSelect);
                }
            }

            function initOutboundEnquiryFormScope($scope) {
                $scope.find('.js-select').not('.outbound-enquiry-lead-select, .outbound-enquiry-booking-select').each(function () {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({ width: '100%' });
                    }
                });
                applyOutboundEnquiryStatusLinks($scope);
            }

            $(document).on('change', '.outbound-enquiry-status-select', function () {
                applyOutboundEnquiryStatusLinks($(this).closest('.outbound-enquiry-form-fields'));
            });

            $(function () {
                $('.outbound-enquiry-form-fields').each(function () {
                    initOutboundEnquiryFormScope($(this));
                });
            });

            $(document).on('shown.bs.modal', '#addOutboundEnquiryModal', function () {
                const $scope = $(this).find('.outbound-enquiry-form-fields');
                if ($scope.length) {
                    initOutboundEnquiryFormScope($scope);
                }
            });
        })(jQuery);
    </script>
@endpush
