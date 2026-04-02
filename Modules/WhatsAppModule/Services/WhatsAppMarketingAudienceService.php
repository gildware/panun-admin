<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Storage;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class WhatsAppMarketingAudienceService
{
    public function countCustomersWithPhone(): int
    {
        return User::query()
            ->inCustomerDirectory()
            ->where('is_active', 1)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->count();
    }

    public function countProvidersWithPhone(): int
    {
        return Provider::query()
            ->ofApproval(1)
            ->ofStatus(1)
            ->whereHas('user', function ($q) {
                $q->whereNotNull('phone')->where('phone', '!=', '');
            })
            ->count();
    }

    public function countProvidersInCategory(string $categoryId): int
    {
        return Provider::query()
            ->ofApproval(1)
            ->ofStatus(1)
            ->whereHas('subscribed_services', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId)->where('is_subscribed', 1);
            })
            ->whereHas('user', function ($q) {
                $q->whereNotNull('phone')->where('phone', '!=', '');
            })
            ->count();
    }

    /**
     * Preview recipients for DB-backed audiences (deduped like send; sample is first N after dedupe within a batch).
     *
     * @return array{total_matching: int, deduped_in_batch: int, rows: array<int, array{name: string, phone_normalized: string, category_name: string}>, preview_limit: int, has_more: bool, kind: string}
     */
    public function previewDbAudience(
        string $audienceType,
        ?string $categoryId,
        int $previewLimit = 50,
        int $fetchBatch = 2500
    ): array {
        if ($audienceType === 'providers_by_category' && !$categoryId) {
            return [
                'total_matching' => 0,
                'deduped_in_batch' => 0,
                'rows' => [],
                'preview_limit' => $previewLimit,
                'has_more' => false,
                'kind' => 'needs_category',
            ];
        }

        if (!in_array($audienceType, ['all_customers', 'all_providers', 'providers_by_category'], true)) {
            return [
                'total_matching' => 0,
                'deduped_in_batch' => 0,
                'rows' => [],
                'preview_limit' => $previewLimit,
                'has_more' => false,
                'kind' => 'unsupported',
            ];
        }

        $fetchBatch = max(100, min($fetchBatch, 5000));

        $raw = match ($audienceType) {
            'all_customers' => $this->fetchCustomersRaw($fetchBatch),
            'all_providers' => $this->fetchProvidersRaw($fetchBatch),
            'providers_by_category' => $this->fetchProvidersByCategoryRaw((string) $categoryId, $fetchBatch),
            default => [],
        };

        $deduped = $this->uniqueByNormalizedPhone($raw);

        $totalMatching = match ($audienceType) {
            'all_customers' => $this->countCustomersWithPhone(),
            'all_providers' => $this->countProvidersWithPhone(),
            'providers_by_category' => $this->countProvidersInCategory((string) $categoryId),
            default => 0,
        };

        $slice = array_slice($deduped, 0, $previewLimit);
        $rows = [];
        foreach ($slice as $r) {
            $rows[] = [
                'name' => $r['name'],
                'phone_normalized' => $r['phone'],
                'category_name' => $r['category_name'] ?? '',
            ];
        }

        return [
            'total_matching' => $totalMatching,
            'deduped_in_batch' => count($deduped),
            'rows' => $rows,
            'preview_limit' => $previewLimit,
            'has_more' => $totalMatching > count($rows),
            'kind' => $audienceType,
        ];
    }

    /**
     * Remove recipients by normalized phone, then append extra contacts (skips phones in the exclude set).
     * Preserves optional per-row "client_id" for UI tracking of manually added rows.
     *
     * @param  array<int, array{name: string, phone: string, category_name?: string, client_id?: string|null}>  $recipients
     * @param  array<int, string|mixed>  $excludePhonesRaw
     * @param  array<int, array{name?: string, phone?: string, category_name?: string, client_id?: string|null}>  $extraContacts
     * @return array<int, array{name: string, phone: string, category_name: string, client_id?: string|null}>
     */
    public function applyRecipientAdjustments(array $recipients, array $excludePhonesRaw, array $extraContacts): array
    {
        $cloud = app(WhatsAppCloudService::class);
        $excluded = [];
        foreach ($excludePhonesRaw as $p) {
            $n = $cloud->normalizeRecipientPhone((string) $p);
            if ($n !== null) {
                $excluded[$n] = true;
            }
        }

        $out = [];
        foreach ($recipients as $r) {
            if (isset($excluded[$r['phone']])) {
                continue;
            }
            $out[] = $r;
        }

        foreach ($extraContacts as $extra) {
            if (!is_array($extra)) {
                continue;
            }
            $phone = $cloud->normalizeRecipientPhone((string) ($extra['phone'] ?? ''));
            if ($phone === null || isset($excluded[$phone])) {
                continue;
            }
            $name = trim((string) ($extra['name'] ?? ''));
            $cid = $extra['client_id'] ?? null;
            $out[] = [
                'name' => $name !== '' ? $name : 'Contact',
                'phone' => $phone,
                'category_name' => (string) ($extra['category_name'] ?? ''),
                'client_id' => $cid !== null && $cid !== '' ? (string) $cid : null,
            ];
        }

        return $this->uniqueByNormalizedPhone($out);
    }

    /**
     * Full recipient list (same as send), with adjustments, preview slice and totals.
     *
     * @param  array<int, string|mixed>  $excludePhonesRaw
     * @param  array<int, array<string, mixed>>  $extraContacts
     * @return array{total_matching: int, rows: array<int, array<string, mixed>>, preview_limit: int, has_more: bool, kind: string}
     */
    public function previewRecipientsMerged(
        string $audienceType,
        ?string $categoryId,
        ?string $csvDiskPath,
        array $excludePhonesRaw,
        array $extraContacts,
        int $previewLimit = 50
    ): array {
        if ($audienceType === 'providers_by_category' && !$categoryId) {
            return [
                'total_matching' => 0,
                'rows' => [],
                'preview_limit' => $previewLimit,
                'has_more' => false,
                'kind' => 'needs_category',
            ];
        }

        if (!in_array($audienceType, ['all_customers', 'all_providers', 'providers_by_category', 'csv_import'], true)) {
            return [
                'total_matching' => 0,
                'rows' => [],
                'preview_limit' => $previewLimit,
                'has_more' => false,
                'kind' => 'unsupported',
            ];
        }

        $recipients = $this->resolve($audienceType, $categoryId, $csvDiskPath);
        $merged = $this->applyRecipientAdjustments($recipients, $excludePhonesRaw, $extraContacts);
        $total = count($merged);
        $slice = array_slice($merged, 0, $previewLimit);
        $rows = [];
        foreach ($slice as $r) {
            $row = [
                'name' => $r['name'],
                'phone_normalized' => $r['phone'],
                'category_name' => $r['category_name'] ?? '',
                'is_manual' => !empty($r['client_id'] ?? null),
            ];
            if (!empty($r['client_id'] ?? null)) {
                $row['client_id'] = (string) $r['client_id'];
            }
            $rows[] = $row;
        }

        return [
            'total_matching' => $total,
            'rows' => $rows,
            'preview_limit' => $previewLimit,
            'has_more' => $total > $previewLimit,
            'kind' => $audienceType === 'csv_import' ? 'csv_import' : $audienceType,
        ];
    }

    /**
     * @return array<int, array{name: string, phone: string, category_name: string}>
     */
    private function fetchCustomersRaw(int $limit): array
    {
        return User::query()
            ->inCustomerDirectory()
            ->where('is_active', 1)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('id')
            ->limit($limit)
            ->get(['first_name', 'last_name', 'phone'])
            ->map(function (User $u) {
                return [
                    'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                    'phone' => (string) $u->phone,
                    'category_name' => '',
                ];
            })->all();
    }

    /**
     * @return array<int, array{name: string, phone: string, category_name: string}>
     */
    private function fetchProvidersRaw(int $limit): array
    {
        $providers = Provider::query()
            ->ofApproval(1)
            ->ofStatus(1)
            ->whereHas('user', function ($q) {
                $q->whereNotNull('phone')->where('phone', '!=', '');
            })
            ->with(['user' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'phone', 'user_type')])
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'company_name', 'user_id']);

        $list = [];
        foreach ($providers as $p) {
            $user = $p->user;
            if (!$user || trim((string) $user->phone) === '') {
                continue;
            }
            $name = $p->company_name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $list[] = [
                'name' => $name !== '' ? $name : 'Provider',
                'phone' => (string) $user->phone,
                'category_name' => '',
            ];
        }

        return $list;
    }

    /**
     * @return array<int, array{name: string, phone: string, category_name: string}>
     */
    private function fetchProvidersByCategoryRaw(string $categoryId, int $limit): array
    {
        $categoryName = (string) (Category::query()->where('id', $categoryId)->value('name') ?? '');

        $providers = Provider::query()
            ->ofApproval(1)
            ->ofStatus(1)
            ->whereHas('subscribed_services', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId)->where('is_subscribed', 1);
            })
            ->whereHas('user', function ($q) {
                $q->whereNotNull('phone')->where('phone', '!=', '');
            })
            ->with(['user' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'phone', 'user_type')])
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'company_name', 'user_id']);

        $list = [];
        foreach ($providers as $p) {
            $user = $p->user;
            if (!$user || trim((string) $user->phone) === '') {
                continue;
            }
            $name = $p->company_name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $list[] = [
                'name' => $name !== '' ? $name : 'Provider',
                'phone' => (string) $user->phone,
                'category_name' => $categoryName,
            ];
        }

        return $list;
    }

    /**
     * @return array<int, array{name: string, phone: string, category_name: string}>
     */
    public function resolve(string $audienceType, ?string $categoryId, ?string $csvDiskPath): array
    {
        return match ($audienceType) {
            'all_customers' => $this->allCustomers(),
            'all_providers' => $this->allProviders(),
            'providers_by_category' => $this->providersByCategory($categoryId),
            'csv_import' => $this->fromCsv($csvDiskPath),
            default => [],
        };
    }

    /**
     * @return array<int, array{name: string, phone: string, category_name: string}>
     */
    private function allCustomers(): array
    {
        $rows = User::query()
            ->inCustomerDirectory()
            ->where('is_active', 1)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get(['first_name', 'last_name', 'phone']);

        return $this->uniqueByNormalizedPhone($rows->map(function (User $u) {
            return [
                'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                'phone' => (string) $u->phone,
                'category_name' => '',
            ];
        })->all());
    }

    /**
     * @return array<int, array{name: string, phone: string, category_name: string}>
     */
    private function allProviders(): array
    {
        $providers = Provider::query()
            ->ofApproval(1)
            ->ofStatus(1)
            ->with(['user' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'phone', 'user_type')])
            ->get(['id', 'company_name', 'user_id']);

        $list = [];
        foreach ($providers as $p) {
            $user = $p->user;
            if (!$user || trim((string) $user->phone) === '') {
                continue;
            }
            $name = $p->company_name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $list[] = [
                'name' => $name !== '' ? $name : 'Provider',
                'phone' => (string) $user->phone,
                'category_name' => '',
            ];
        }

        return $this->uniqueByNormalizedPhone($list);
    }

    /**
     * @return array<int, array{name: string, phone: string, category_name: string}>
     */
    private function providersByCategory(?string $categoryId): array
    {
        if (!$categoryId) {
            return [];
        }

        $categoryName = (string) (Category::query()->where('id', $categoryId)->value('name') ?? '');

        $providers = Provider::query()
            ->ofApproval(1)
            ->ofStatus(1)
            ->whereHas('subscribed_services', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId)->where('is_subscribed', 1);
            })
            ->with(['user' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'phone', 'user_type')])
            ->get(['id', 'company_name', 'user_id']);

        $list = [];
        foreach ($providers as $p) {
            $user = $p->user;
            if (!$user || trim((string) $user->phone) === '') {
                continue;
            }
            $name = $p->company_name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $list[] = [
                'name' => $name !== '' ? $name : 'Provider',
                'phone' => (string) $user->phone,
                'category_name' => $categoryName,
            ];
        }

        return $this->uniqueByNormalizedPhone($list);
    }

    /**
     * @return array<int, array{name: string, phone: string, category_name: string}>
     */
    private function fromCsv(?string $relativePath): array
    {
        if (!$relativePath || !Storage::disk('local')->exists($relativePath)) {
            return [];
        }

        $full = Storage::disk('local')->path($relativePath);
        if (!is_readable($full)) {
            return [];
        }

        $fh = fopen($full, 'r');
        if ($fh === false) {
            return [];
        }

        $header = fgetcsv($fh);
        if ($header === false) {
            fclose($fh);

            return [];
        }

        $map = $this->normalizeCsvHeader($header);
        $rows = [];

        if ($map['phone'] !== null) {
            while (($row = fgetcsv($fh)) !== false) {
                $phone = isset($row[$map['phone']]) ? trim((string) $row[$map['phone']]) : '';
                if ($phone === '') {
                    continue;
                }
                $name = $map['name'] !== null && isset($row[$map['name']])
                    ? trim((string) $row[$map['name']])
                    : '';
                $rows[] = ['name' => $name, 'phone' => $phone, 'category_name' => ''];
            }
        } elseif (count($header) >= 2) {
            $first = $this->rowFromPositionalColumns($header);
            if ($first !== null) {
                $rows[] = $first;
            }
            while (($row = fgetcsv($fh)) !== false) {
                $parsed = $this->rowFromPositionalColumns($row);
                if ($parsed !== null) {
                    $rows[] = $parsed;
                }
            }
        }

        fclose($fh);

        return $this->uniqueByNormalizedPhone($rows);
    }

    /**
     * @param  array<int, string|null>  $row
     * @return array{name: string, phone: string, category_name: string}|null
     */
    private function rowFromPositionalColumns(array $row): ?array
    {
        $name = trim((string) ($row[0] ?? ''));
        $phone = trim((string) ($row[1] ?? ''));
        if ($phone === '') {
            return null;
        }

        return ['name' => $name, 'phone' => $phone, 'category_name' => ''];
    }

    /**
     * @param  array<int, string>  $header
     * @return array{name: ?int, phone: ?int}
     */
    private function normalizeCsvHeader(array $header): array
    {
        $nameIdx = null;
        $phoneIdx = null;
        foreach ($header as $i => $col) {
            $h = strtolower(trim((string) $col));
            if (in_array($h, ['name', 'full_name', 'contact_name'], true)) {
                $nameIdx = (int) $i;
            }
            if (in_array($h, ['phone', 'mobile', 'phone_number', 'whatsapp', 'msisdn', 'tel', 'cell', 'contact_number'], true)) {
                $phoneIdx = (int) $i;
            }
        }

        return ['name' => $nameIdx, 'phone' => $phoneIdx];
    }

    /**
     * @param  array<int, array{name: string, phone: string, category_name: string}>  $rows
     * @return array<int, array{name: string, phone: string, category_name: string}>
     */
    private function uniqueByNormalizedPhone(array $rows): array
    {
        $cloud = app(WhatsAppCloudService::class);
        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $norm = $cloud->normalizeRecipientPhone($row['phone']);
            if ($norm === null || isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $row['phone'] = $norm;
            $out[] = $row;
        }

        return array_values($out);
    }
}
