<?php

return [
    'name' => 'LeadManagement',

    /**
     * Days after non-responsive close when an inbound call/WhatsApp may auto-reopen
     * a customer or unknown lead. Provider leads always reopen (ignore this window).
     */
    'inbound_non_responsive_reopen_days' => (int) env('INBOUND_NR_REOPEN_DAYS', 7),
];
