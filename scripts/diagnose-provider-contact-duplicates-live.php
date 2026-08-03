<?php

/**
 * Find active provider-admin phone/email and provider contact-phone duplicates
 * blocking 2026_07_25_103000_add_active_provider_contact_unique_indexes.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/diagnose-provider-contact-duplicates-live.php
 *
 * Optional fix (soft-delete surplus rows, keeps best candidate per duplicate group):
 *   PROVIDER_DUP_DRY_RUN=0 PROVIDER_DUP_FIX=1 LIVE_DB_PASSWORD='...' php artisan tinker scripts/diagnose-provider-contact-duplicates-live.php
 */

use Illuminate\Support\Facades\DB;

$fix = filter_var(env('PROVIDER_DUP_FIX', false), FILTER_VALIDATE_BOOLEAN);
$dryRun = ! $fix || filter_var(env('PROVIDER_DUP_DRY_RUN', true), FILTER_VALIDATE_BOOLEAN);

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

echo "=== Provider contact duplicates (live) ===\n";
echo $fix ? ($dryRun ? "Mode: FIX (dry run)\n\n" : "Mode: FIX (will soft-delete surplus rows)\n\n") : "Mode: diagnose only\n\n";

$phoneDupGroups = $conn->select("
    SELECT REGEXP_REPLACE(COALESCE(phone, ''), '[^0-9]', '') AS digits,
           COUNT(*) AS c,
           GROUP_CONCAT(id ORDER BY created_at SEPARATOR ',') AS ids
    FROM users
    WHERE deleted_at IS NULL
      AND user_type = 'provider-admin'
      AND phone IS NOT NULL
      AND phone <> ''
    GROUP BY digits
    HAVING c > 1
");

$emailDupGroups = $conn->select("
    SELECT LOWER(email) AS email_key,
           COUNT(*) AS c,
           GROUP_CONCAT(id ORDER BY created_at SEPARATOR ',') AS ids
    FROM users
    WHERE deleted_at IS NULL
      AND user_type = 'provider-admin'
      AND email IS NOT NULL
      AND email <> ''
    GROUP BY email_key
    HAVING c > 1
");

$providerPhoneDupGroups = $conn->select("
    SELECT REGEXP_REPLACE(COALESCE(contact_person_phone, ''), '[^0-9]', '') AS digits,
           COUNT(*) AS c,
           GROUP_CONCAT(id ORDER BY created_at SEPARATOR ',') AS ids
    FROM providers
    WHERE deleted_at IS NULL
      AND contact_person_phone IS NOT NULL
      AND contact_person_phone <> ''
    GROUP BY digits
    HAVING c > 1
");

echo 'Provider-admin phone duplicate groups: '.count($phoneDupGroups)."\n";
echo 'Provider-admin email duplicate groups: '.count($emailDupGroups)."\n";
echo 'Provider contact_person_phone duplicate groups: '.count($providerPhoneDupGroups)."\n\n";

$describeUser = function (string $userId) use ($conn): array {
    $row = $conn->selectOne("
        SELECT u.id, u.phone, u.email, u.first_name, u.last_name, u.is_active, u.created_at,
               p.id AS provider_id, p.company_name, p.is_active AS provider_active, p.is_approved,
               (SELECT COUNT(*) FROM bookings b WHERE b.provider_id = p.id) AS booking_count
        FROM users u
        LEFT JOIN providers p ON p.user_id = u.id AND p.deleted_at IS NULL
        WHERE u.id = ?
    ", [$userId]);

    return $row ? (array) $row : ['id' => $userId, 'missing' => true];
};

$scoreUser = function (array $row): int {
    $score = 0;
    if (! empty($row['provider_id'])) {
        $score += 10;
    }
    if ((int) ($row['is_approved'] ?? 0) === 1) {
        $score += 50;
    }
    if ((int) ($row['provider_active'] ?? 0) === 1) {
        $score += 20;
    }
    if ((int) ($row['is_active'] ?? 0) === 1) {
        $score += 10;
    }
    $score += min(100, (int) ($row['booking_count'] ?? 0) * 5);

    return $score;
};

$pickKeeperAndSurplus = function (array $ids) use ($conn, $describeUser, $scoreUser): array {
    $rows = [];
    foreach ($ids as $id) {
        $rows[] = $describeUser($id);
    }

    usort($rows, function (array $a, array $b) use ($scoreUser) {
        $scoreDiff = $scoreUser($b) <=> $scoreUser($a);
        if ($scoreDiff !== 0) {
            return $scoreDiff;
        }

        return strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? ''));
    });

    $keeper = $rows[0];
    $surplus = array_slice($rows, 1);

    return [$keeper, $surplus];
};

$softDeleteUserAndProvider = function (string $userId) use ($conn, $dryRun): void {
    $provider = $conn->table('providers')->where('user_id', $userId)->whereNull('deleted_at')->first();
    if ($provider) {
        echo "  soft-delete provider {$provider->id} ({$provider->company_name})\n";
        if (! $dryRun) {
            $conn->table('providers')->where('id', $provider->id)->update(['deleted_at' => now()]);
        }
    }

    echo "  soft-delete user {$userId}\n";
    if (! $dryRun) {
        $conn->table('users')->where('id', $userId)->update(['deleted_at' => now()]);
    }
};

$softDeleteProvider = function (string $providerId) use ($conn, $dryRun): void {
    $provider = $conn->table('providers')->where('id', $providerId)->whereNull('deleted_at')->first();
    if (! $provider) {
        return;
    }

    echo "  soft-delete provider {$providerId} ({$provider->company_name})\n";
    if (! $dryRun) {
        $conn->table('providers')->where('id', $providerId)->update(['deleted_at' => now()]);
    }
};

foreach ($phoneDupGroups as $group) {
    echo "--- Phone {$group->digits} ({$group->c} users) ---\n";
    $ids = explode(',', (string) $group->ids);
    [$keeper, $surplus] = $pickKeeperAndSurplus($ids);

    echo '  KEEP user '.$keeper['id']
        .' | '.$keeper['phone']
        .' | '.$keeper['company_name']
        .' | bookings='.($keeper['booking_count'] ?? 0)
        .' | approved='.($keeper['is_approved'] ?? '?')
        ."\n";

    foreach ($surplus as $row) {
        echo '  SURPLUS user '.$row['id']
            .' | '.$row['phone']
            .' | '.$row['company_name']
            .' | bookings='.($row['booking_count'] ?? 0)
            .' | approved='.($row['is_approved'] ?? '?')
            ."\n";
        if ($fix) {
            $softDeleteUserAndProvider((string) $row['id']);
        }
    }
    echo "\n";
}

foreach ($emailDupGroups as $group) {
    echo "--- Email {$group->email_key} ({$group->c} users) ---\n";
    $ids = explode(',', (string) $group->ids);
    [$keeper, $surplus] = $pickKeeperAndSurplus($ids);

    echo '  KEEP user '.$keeper['id'].' | '.$keeper['email']."\n";
    foreach ($surplus as $row) {
        echo '  SURPLUS user '.$row['id'].' | '.$row['email']."\n";
        if ($fix) {
            $softDeleteUserAndProvider((string) $row['id']);
        }
    }
    echo "\n";
}

foreach ($providerPhoneDupGroups as $group) {
    echo "--- Provider contact phone {$group->digits} ({$group->c} providers) ---\n";
    $ids = explode(',', (string) $group->ids);
    $providers = [];
    foreach ($ids as $id) {
        $providers[] = (array) $conn->selectOne("
            SELECT p.id, p.company_name, p.user_id, p.is_approved, p.is_active, p.created_at,
                   (SELECT COUNT(*) FROM bookings b WHERE b.provider_id = p.id) AS booking_count
            FROM providers p
            WHERE p.id = ?
        ", [$id]);
    }

    usort($providers, function (array $a, array $b) {
        $score = fn (array $row) => ((int) ($row['is_approved'] ?? 0) === 1 ? 50 : 0)
            + ((int) ($row['is_active'] ?? 0) === 1 ? 20 : 0)
            + min(100, (int) ($row['booking_count'] ?? 0) * 5);

        $diff = $score($b) <=> $score($a);
        if ($diff !== 0) {
            return $diff;
        }

        return strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? ''));
    });

    echo '  KEEP provider '.$providers[0]['id'].' | '.$providers[0]['company_name']."\n";
    foreach (array_slice($providers, 1) as $row) {
        echo '  SURPLUS provider '.$row['id'].' | '.$row['company_name']."\n";
        if ($fix) {
            $softDeleteProvider((string) $row['id']);
        }
    }
    echo "\n";
}

if ($fix && $dryRun) {
    echo "Dry run only — re-run with PROVIDER_DUP_DRY_RUN=0 to apply.\n";
} elseif ($fix) {
    echo "Fix applied. Re-run migrate on live.\n";
} else {
    echo "Diagnosis complete. Review SURPLUS rows, then re-run with PROVIDER_DUP_FIX=1.\n";
}
