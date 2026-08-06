<div class="finance-kpi-sections">
    <section class="finance-kpi-section finance-kpi-section--revenue">
        <h2 class="finance-kpi-section__title">
            <span class="material-symbols-outlined">payments</span>
            <span>{{ translate('Finance_kpi_section_revenue') }}</span>
        </h2>
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card finance-kpi-card--green">
                <span class="finance-kpi-card__icon material-symbols-outlined">account_balance_wallet</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.total_revenue', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Total_Revenue') }}</div>
                </div>
            </div>
            <div class="finance-kpi-card finance-kpi-card--violet">
                <span class="finance-kpi-card__icon material-symbols-outlined">home_repair_service</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.service_charges_total', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Service_Charges') }}</div>
                </div>
            </div>
            <div class="finance-kpi-card finance-kpi-card--orange">
                <span class="finance-kpi-card__icon material-symbols-outlined">inventory_2</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.spare_parts_total', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Parts_Charges') }}</div>
                </div>
            </div>
            <div class="finance-kpi-card finance-kpi-card--teal">
                <span class="finance-kpi-card__icon material-symbols-outlined">move_to_inbox</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.total_amount_received_by_company', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Total_amount_received') }}</div>
                </div>
            </div>
            <div class="finance-kpi-card finance-kpi-card--green">
                <span class="finance-kpi-card__icon material-symbols-outlined">trending_up</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.our_earning', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Our_Earning') }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="finance-kpi-section finance-kpi-section--balances">
        <h2 class="finance-kpi-section__title">
            <span class="material-symbols-outlined">account_balance</span>
            <span>{{ translate('Finance_kpi_section_balances') }}</span>
        </h2>
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card finance-kpi-card--blue">
                <span class="finance-kpi-card__icon material-symbols-outlined">engineering</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.payable_to_providers', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Payable_to_providers') }}</div>
                </div>
            </div>
            <div class="finance-kpi-card finance-kpi-card--cyan">
                <span class="finance-kpi-card__icon material-symbols-outlined">payments</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.unsettled_withdraws_total', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('UnSettled_Withdraws_Amount') }}</div>
                    <div class="finance-kpi-card__meta">
                        <span>{{ translate('Pending_Withdraw') }}: {{ with_currency_symbol(data_get($data[0], 'top_cards.unsettled_withdraws_pending', 0)) }}</span>
                        <span>{{ translate('Approved') }}: {{ with_currency_symbol(data_get($data[0], 'top_cards.unsettled_withdraws_approved', 0)) }}</span>
                    </div>
                </div>
            </div>
            <div class="finance-kpi-card finance-kpi-card--amber">
                <span class="finance-kpi-card__icon material-symbols-outlined">compare_arrows</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.balance_with_providers', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Balance_With_Providers') }}</div>
                </div>
            </div>
            <div class="finance-kpi-card finance-kpi-card--pink">
                <span class="finance-kpi-card__icon material-symbols-outlined">person</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.payable_to_customers', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Payable_to_customer') }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="finance-kpi-section finance-kpi-section--losses">
        <h2 class="finance-kpi-section__title">
            <span class="material-symbols-outlined">warning</span>
            <span>{{ translate('Finance_kpi_section_losses') }}</span>
        </h2>
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card finance-kpi-card--red">
                <span class="finance-kpi-card__icon material-symbols-outlined">trending_down</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.total_loss_in_all_bookings', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Total_loss_in_all_bookings') }}</div>
                </div>
            </div>
            <div class="finance-kpi-card finance-kpi-card--rose">
                <span class="finance-kpi-card__icon material-symbols-outlined">gavel</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.total_bad_debt_with_customers', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Dashboard_company_loss_from_customers') }}</div>
                </div>
            </div>
            <div class="finance-kpi-card finance-kpi-card--red">
                <span class="finance-kpi-card__icon material-symbols-outlined">percent</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.total_write_off_company', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Dashboard_write_off_company_total') }}</div>
                </div>
            </div>
            <div class="finance-kpi-card finance-kpi-card--orange">
                <span class="finance-kpi-card__icon material-symbols-outlined">percent</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[0], 'top_cards.total_write_off_provider', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Dashboard_write_off_provider_total') }}</div>
                </div>
            </div>
            <div class="finance-kpi-card finance-kpi-card--rose">
                <span class="finance-kpi-card__icon material-symbols-outlined">volunteer_activism</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[2], 'compensation_totals.company_to_customers', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Company_compensation_to_customers') }}</div>
                </div>
            </div>
            <div class="finance-kpi-card finance-kpi-card--amber">
                <span class="finance-kpi-card__icon material-symbols-outlined">handshake</span>
                <div class="finance-kpi-card__body">
                    <div class="finance-kpi-card__value">{{ with_currency_symbol(data_get($data[2], 'compensation_totals.company_to_providers', 0)) }}</div>
                    <div class="finance-kpi-card__label">{{ translate('Company_compensation_to_providers') }}</div>
                </div>
            </div>
        </div>
    </section>
</div>
