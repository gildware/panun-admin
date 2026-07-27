<?php

/**
 * Find bookings with multiple scheduled follow-ups for the same party (customer/provider).
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/diagnose-duplicate-booking-followups-live.php
 *
 * Optional:
 *   DUPLICATE_FU_DRY_RUN=0 DUPLICATE_FU_FIX=1  — cancel surplus scheduled rows (keeps newest per booking+party)
 */

use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;

$fix = filter_var(env('DUPLICATE_FU_FIX', false), FILTER_VALIDATE_BOOLEAN);
$dryRun = ! $fix || filter_var(env('DUPLICATE_FU_DRY_RUN', true), FILTER_VALIDATE_BOOLEAN);

$liveConfig = [
    'driver' => 'mysql',
    'host' => env('IMPORT_DB_HOST', env('TARGET_DB_HOST', '82.25.121.201')),
    'port' => env('IMPORT_DB_PORT', env('TARGET_DB_PORT', '3306')),
    'database' => env('IMPORT_DB_DATABASE', env('TARGET_DB_DATABASE', 'u397782854_live_pk_dec')),
    'username' => env('IMPORT_DB_USERNAME', env('TARGET_DB_USERNAME', 'u397782854_live_pk_usr')),
    'password' => env('LIVE_DB_PASSWORD', env('IMPORT_DB_PASSWORD', env('TARGET_DB_PASSWORD', env('DB_PASSWORD', '')))),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
];

if ($liveConfig['password'] === '') {
    throw new RuntimeException('Set LIVE_DB_PASSWORD (or DB_PASSWORD) for the target database.');
}

config(['database.connections.live_diag' => $liveConfig]);
$conn = DB::connection('live_diag');

$activeStatuses = Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS ?? ['pending', 'accepted', 'ongoing', 'on_hold'];

$rows = $conn->table('booking_followups as bf')
    ->join('bookings as b', 'b.id', '=', 'bf.booking_id')
    ->where('bf.status', 'scheduled')
    ->selectRaw('b.readable_id, b.booking_status, bf.booking_id, bf.for, COUNT(*) as cnt')
    ->groupBy('b.readable_id', 'b.booking_status', 'bf.booking_id', 'bf.for')
    ->havingRaw('COUNT(*) > 1')
    ->orderByDesc('cnt')
    ->orderBy('b.readable_id')
    ->get();

$grouped = [];
foreach ($rows as $row) {
    $key = (string) $row->readable_id;
    if (! isset($grouped[$key])) {
        $grouped[$key] = [
            'readable_id' => $row->readable_id,
            'booking_status' => $row->booking_status,
            'booking_id' => $row->booking_id,
            'customer' => 0,
            'provider' => 0,
        ];
    }
    $grouped[$key][$row->for] = (int) $row->cnt;
}

$active = array_values(array_filter($grouped, fn ($g) => in_array($g['booking_status'], $activeStatuses, true)));
$all = array_values($grouped);

echo "=== Duplicate scheduled booking follow-ups (same party) ===\n";
echo 'Active bookings affected: '.count($active)."\n";
echo 'All bookings affected: '.count($all)."\n\n";

foreach ($all as $g) {
    $flag = in_array($g['booking_status'], $activeStatuses, true) ? '*' : ' ';
    $total = $g['customer'] + $g['provider'];
    echo sprintf(
        "%s %-18s %-10s customer=%d provider=%d total=%d\n",
        $flag,
        $g['readable_id'],
        $g['booking_status'],
        $g['customer'],
        $g['provider'],
        $total
    );
}

if ($fix) {
    echo "\n=== Cleanup ".($dryRun ? '(DRY RUN)' : '(LIVE)')." ===\n";
    $cancelled = 0;

    $conn->transaction(function () use ($conn, $dryRun, &$cancelled) {
        $dupes = $conn->table('booking_followups')
            ->where('status', 'scheduled')
            ->selectRaw('booking_id, `for`, GROUP_CONCAT(id ORDER BY date DESC, id DESC) as ids')
            ->groupBy('booking_id', 'for')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupes as $dupe) {
            $ids = array_map('intval', explode(',', (string) $dupe->ids));
            $keep = array_shift($ids);
            if ($ids === []) {
                continue;
            }
            echo "booking_id={$dupe->booking_id} for={$dupe->for} keep={$keep} cancel=".implode(',', $ids)."\n";
            if (! $dryRun) {
                foreach ($ids as $cancelId) {
                    $row = $conn->table('booking_followups')->where('id', $cancelId)->first(['remarks']);
                    $remarks = trim((string) ($row->remarks ?? ''));
                    $note = 'Auto-cancelled duplicate scheduled follow-up';
                    $cancelled += $conn->table('booking_followups')
                        ->where('id', $cancelId)
                        ->update([
                            'status' => 'cancelled',
                            'remarks' => $remarks === '' ? $note : $remarks.' | '.$note,
                            'updated_at' => now(),
                        ]);
                }
            } else {
                $cancelled += count($ids);
            }
        }
    });

    echo "Rows ".($dryRun ? 'would be ' : '')."cancelled: {$cancelled}\n";
}
