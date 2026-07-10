<?php

/**
 * Seed Service Overview Defaults (including Terms & Conditions) on live DB.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/seed-service-overview-defaults-live.php
 */

use Modules\BusinessSettingsModule\Entities\DataSetting;
use Modules\ServiceManagement\Services\ServiceOverviewDefaultsService;

$liveConnection = 'live_service_content';
config(['database.connections.'.$liveConnection => [
    'driver' => 'mysql',
    'host' => '82.25.121.201',
    'port' => '3306',
    'database' => 'u397782854_live_pk_dec',
    'username' => 'u397782854_live_pk_usr',
    'password' => env('LIVE_DB_PASSWORD', env('DB_PASSWORD', '')),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
]]);

if ((string) config('database.connections.'.$liveConnection.'.password') === '') {
    throw new RuntimeException('Set LIVE_DB_PASSWORD (or DB_PASSWORD) for live database.');
}

$base = ServiceOverviewDefaultsService::defaults();

$payload = array_merge($base, [
    'terms_and_conditions' => [
        'title' => 'Terms & Conditions',
        'items' => [
            [
                'icon' => 'pricing',
                'title' => 'Prices shown cover labour and standard materials for the selected package in your service zone. Final cost may change if extra materials or special on-site work is required — your technician will explain before proceeding.',
                'sort_order' => 0,
            ],
            [
                'icon' => 'home',
                'title' => 'Please provide clear access to the work area and keep the site ready before the visit. Delays caused by blocked or inaccessible locations may require rescheduling or a revisit charge.',
                'sort_order' => 1,
            ],
            [
                'icon' => 'warranty',
                'title' => 'Workmanship warranty is provided as per Panun Kaergar policy. Manufacturer or brand warranty on customer-supplied products, parts, or units remains with the original seller or brand.',
                'sort_order' => 2,
            ],
            [
                'icon' => 'support',
                'title' => 'Panun Kaergar is not liable for pre-existing faults, hidden structural issues, poor previous workmanship, or defects in customer-supplied materials or equipment.',
                'sort_order' => 3,
            ],
            [
                'icon' => 'calendar',
                'title' => 'For cancellation or rescheduling, please notify us at least 2 hours before the scheduled slot wherever possible.',
                'sort_order' => 4,
            ],
            [
                'icon' => 'check',
                'title' => 'By booking, you agree that any add-on work identified during the visit is optional and will be charged separately only after on-site assessment and your approval.',
                'sort_order' => 5,
            ],
        ],
    ],
]);

$normalized = ServiceOverviewDefaultsService::normalizeDefaults($payload);

DataSetting::on($liveConnection)->updateOrCreate(
    ['type' => ServiceOverviewDefaultsService::TYPE, 'key' => ServiceOverviewDefaultsService::KEY],
    ['value' => json_encode($normalized), 'is_active' => 1]
);

echo "Seeded service overview defaults on live.\n";
echo 'Terms & Conditions items: '.count($normalized['terms_and_conditions']['items'])."\n";
echo 'Top icons: '.count($normalized['top_icons'])."\n";
echo 'Why choose items: '.count($normalized['why_choose']['items'])."\n";
