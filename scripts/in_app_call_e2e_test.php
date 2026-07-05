<?php
/**
 * In-app call E2E test (HTTP polling path — no Soketi required).
 * Run: php scripts/in_app_call_e2e_test.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\InAppCallModule\Services\InAppCallSignalingTestService;

echo "\n=== In-App Call E2E (HTTP polling, no Soketi) ===\n\n";

$result = app(InAppCallSignalingTestService::class)->run();

if ($result['customer_label'] && $result['provider_label']) {
    echo "Participants: {$result['customer_label']} ↔ {$result['provider_label']}\n";
}
if ($result['channel_id']) {
    echo "Channel: {$result['channel_id']}\n";
}
if ($result['call_id']) {
    echo "Call ID: {$result['call_id']}\n";
}
echo "\n";

foreach ($result['steps'] as $step) {
    $mark = ($step['status'] ?? '') === 'pass' ? '✓ PASS' : '✗ FAIL';
    $detail = $step['detail'] ?? '';
    echo "  {$mark}: {$step['label']}";
    if ($detail !== '') {
        echo " — {$detail}";
    }
    echo "\n";
}

echo "\n=== Results: {$result['passed']} passed, {$result['failed']} failed ({$result['duration_ms']} ms) ===\n\n";

exit($result['ok'] ? 0 : 1);
