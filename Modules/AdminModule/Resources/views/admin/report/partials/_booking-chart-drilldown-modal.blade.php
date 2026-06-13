<div class="modal fade" id="bookingChartDrilldownModal" tabindex="-1" aria-labelledby="bookingChartDrilldownModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookingChartDrilldownModalLabel">{{ translate('Bookings') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body p-0">
                <div id="booking-chart-drilldown-loading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ translate('Loading') }}...</span>
                    </div>
                </div>
                <div id="booking-chart-drilldown-empty" class="text-center text-muted py-5 d-none">
                    {{ translate('No_data_found') }}
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 d-none" id="booking-chart-drilldown-table">
                        <thead class="table-light">
                        <tr>
                            <th>{{ translate('Booking_ID') }}</th>
                            <th>{{ translate('Booking_Status') }}</th>
                            <th>{{ translate('Customer_Info') }}</th>
                            <th>{{ translate('Provider_Info') }}</th>
                            <th>{{ translate('Booking_Amount') }}</th>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody id="booking-chart-drilldown-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
