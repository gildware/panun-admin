<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default company share of visiting / extra_fee (percentage)
    |--------------------------------------------------------------------------
    | Used when settlement outcome applies a flat split on visit charges only.
    | Provider receives the remainder of the visit fee. Service amounts still use
    | normal tier commission when applicable.
    */
    'default_visit_fee_company_percent' => (float) env('BOOKING_VISIT_FEE_COMPANY_PERCENT', 10),

];
