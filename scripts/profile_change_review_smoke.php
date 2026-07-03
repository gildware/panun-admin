<?php

/**
 * Rollback-safe smoke test for profile change review flows.
 * Usage: php scripts/profile_change_review_smoke.php
 */

use Illuminate\Support\Str;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderChangeRequest;
use Modules\ProviderManagement\Services\ProviderProfileChangeRequestService;
use Modules\UserManagement\Entities\User;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if ((config('database.default') ?? '') === 'sqlite') {
    fwrite(STDERR, "SKIP: configure MySQL in .env for this smoke test.\n");
    exit(0);
}

$failures = [];

DB::beginTransaction();

try {
    $adminId = (string) Str::uuid();
    User::query()->create([
        'id' => $adminId,
        'first_name' => 'Smoke',
        'last_name' => 'Admin',
        'email' => 'smoke-admin-'.Str::random(8).'@test.local',
        'phone' => '9'.random_int(100000000, 999999999),
        'password' => bcrypt('password'),
        'user_type' => 'super-admin',
        'is_active' => 1,
    ]);

    $ownerId = (string) Str::uuid();
    User::query()->create([
        'id' => $ownerId,
        'first_name' => 'Smoke',
        'last_name' => 'Provider',
        'email' => 'smoke-provider-'.Str::random(8).'@test.local',
        'phone' => '8'.random_int(100000000, 999999999),
        'password' => bcrypt('password'),
        'user_type' => 'provider-admin',
        'is_active' => 1,
    ]);

    $provider = new Provider();
    $provider->id = (string) Str::uuid();
    $provider->user_id = $ownerId;
    $provider->company_name = 'Smoke Review Provider';
    $provider->company_phone = '7000000099';
    $provider->company_email = 'smoke-co-'.Str::random(8).'@test.local';
    $provider->logo = 'old-logo.png';
    $provider->cover_image = null;
    $provider->is_active = 1;
    $provider->is_approved = 1;
    $provider->save();

    $request = ProviderChangeRequest::query()->create([
        'provider_id' => $provider->id,
        'change_type' => 'branding',
        'status' => ProviderChangeRequest::STATUS_PENDING,
        'payload' => [
            'logo' => 'new-logo.png',
            'cover_image' => 'new-cover.png',
        ],
    ]);

    $service = app(ProviderProfileChangeRequestService::class);

    $single = $service->reviewSingleField($request, 'logo', true, $adminId);
    $request->refresh();
    $provider->refresh();

    if ($single['remaining_count'] !== 1 || $single['request_closed']) {
        $failures[] = 'single approve should leave one pending field';
    }
    if ($provider->logo !== 'new-logo.png') {
        $failures[] = 'single approve should apply logo';
    }
    if ($service->pendingReviewCount($request) !== 1) {
        $failures[] = 'pending count should be 1 after single approve';
    }

    $display = $service->buildReviewDisplayChanges($request);
    if (($display[0]['review_status'] ?? '') !== 'approved' || ($display[1]['review_status'] ?? '') !== 'pending') {
        $failures[] = 'display changes should show approved + pending rows';
    }

    $service->reviewSingleField($request, 'cover_image', false, $adminId);
    $request->refresh();

    if ($request->status !== ProviderChangeRequest::STATUS_APPROVED) {
        $failures[] = 'mixed review should close as approved';
    }

    $request2 = ProviderChangeRequest::query()->create([
        'provider_id' => $provider->id,
        'change_type' => 'branding',
        'status' => ProviderChangeRequest::STATUS_PENDING,
        'payload' => [
            'logo' => 'bulk-logo.png',
            'cover_image' => 'bulk-cover.png',
        ],
    ]);

    foreach ($service->pendingFieldChangesForRequest($request2) as $fieldKey) {
        $service->reviewSingleField($request2, $fieldKey, true, $adminId);
        $request2->refresh();
    }

    $provider->refresh();
    if ($request2->status !== ProviderChangeRequest::STATUS_APPROVED || $provider->logo !== 'bulk-logo.png') {
        $failures[] = 'accept all flow failed';
    }

    $request3 = ProviderChangeRequest::query()->create([
        'provider_id' => $provider->id,
        'change_type' => 'branding',
        'status' => ProviderChangeRequest::STATUS_PENDING,
        'payload' => [
            'logo' => 'deny-logo.png',
            'cover_image' => 'deny-cover.png',
        ],
    ]);

    foreach ($service->pendingFieldChangesForRequest($request3) as $fieldKey) {
        $service->reviewSingleField($request3, $fieldKey, false, $adminId);
        $request3->refresh();
    }

    if ($request3->status !== ProviderChangeRequest::STATUS_DENIED) {
        $failures[] = 'deny all flow failed';
    }

    $request4 = $service->submit($provider->id, 'branding', [
        'cover_image' => 'sequential-cover.png',
    ]);
    $request4 = $service->submit($provider->id, 'branding', [
        'logo' => 'sequential-logo.png',
    ]);

    if ($request4->status !== ProviderChangeRequest::STATUS_PENDING) {
        $failures[] = 'sequential branding submit should keep request pending';
    }
    if (($request4->payload['cover_image'] ?? '') !== 'sequential-cover.png') {
        $failures[] = 'sequential branding submit should keep earlier cover_image';
    }
    if (($request4->payload['logo'] ?? '') !== 'sequential-logo.png') {
        $failures[] = 'sequential branding submit should add logo';
    }
    if (ProviderChangeRequest::query()
        ->where('provider_id', $provider->id)
        ->where('change_type', 'branding')
        ->where('status', ProviderChangeRequest::STATUS_DENIED)
        ->where('payload->cover_image', 'sequential-cover.png')
        ->exists()) {
        $failures[] = 'sequential branding submit must not auto-deny prior cover request';
    }
} catch (Throwable $e) {
    $failures[] = $e->getMessage();
} finally {
    DB::rollBack();
}

if ($failures !== []) {
    fwrite(STDERR, "FAIL\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "PASS: single approve, single deny, accept all, deny all, sequential branding merge\n";
exit(0);
