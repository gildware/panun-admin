<?php

namespace Modules\TransactionModule\Services;

use Illuminate\Support\Collection;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Services\ProviderBookingSettlementNetResolver;
use Modules\TransactionModule\Entities\LedgerTransaction;

class PendingProviderBalanceListingService
{
    public function __construct(
        protected ProviderBookingSettlementNetResolver $settlementResolver,
    ) {}

    public function categoriesForFilter(): Collection
    {
        return Category::query()
            ->ofType('main')
            ->ofStatus(1)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return array{
     *     rows: list<array{
     *         provider_id: string,
     *         provider_name: string,
     *         category_label: string,
     *         balance_due: float,
     *         last_payment_amount: ?float,
     *         last_payment_date: ?\Carbon\CarbonInterface
     *     }>,
     *     summary: array{
     *         providers_owe_us: float,
     *         we_owe_providers: float,
     *         total_net: float
     *     }
     * }
     */
    public function buildListing(
        ?string $search,
        ?string $categoryId,
        string $sort,
        string $balanceFilter = 'all',
    ): array {
        $rows = $this->buildAllRows($search, $categoryId);
        $summary = $this->summarizeBalances($rows);
        $rows = $this->filterRowsByBalance($rows, $balanceFilter);

        usort($rows, function ($a, $b) use ($sort) {
            return match ($sort) {
                'balance_asc' => $a['balance_due'] <=> $b['balance_due'],
                'name_asc' => strcmp((string) $a['provider_name'], (string) $b['provider_name']),
                default => $b['balance_due'] <=> $a['balance_due'],
            };
        });

        return [
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    /**
     * @deprecated Use buildListing() — kept for callers that only need rows.
     *
     * @return list<array<string, mixed>>
     */
    public function buildRows(?string $search, ?string $categoryId, string $sort, string $balanceFilter = 'all'): array
    {
        return $this->buildListing($search, $categoryId, $sort, $balanceFilter)['rows'];
    }

    /**
     * @return list<array{
     *     provider_id: string,
     *     provider_name: string,
     *     category_label: string,
     *     balance_due: float,
     *     last_payment_amount: ?float,
     *     last_payment_date: ?\Carbon\CarbonInterface
     * }>
     */
    protected function buildAllRows(?string $search, ?string $categoryId): array
    {
        $query = Provider::query()
            ->where('is_approved', 1)
            ->with([
                'owner.account',
                'subscribed_services' => fn ($q) => $q->ofStatus(1)->with('category'),
            ]);

        $search = $search !== null ? trim($search) : '';
        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $like = '%' . $escaped . '%';
            $query->where(function ($q) use ($like) {
                $q->where('company_name', 'like', $like)
                    ->orWhere('contact_person_phone', 'like', $like)
                    ->orWhere('company_phone', 'like', $like)
                    ->orWhereHas('owner', fn ($u) => $u->where('phone', 'like', $like));
            });
        }

        if ($categoryId !== null && $categoryId !== '') {
            $query->whereHas(
                'subscribed_services',
                fn ($ss) => $ss->ofStatus(1)->where('category_id', $categoryId)
            );
        }

        $providers = $query->get();

        $lastCollectIds = LedgerTransaction::query()
            ->selectRaw('max(id) as id')
            ->where('type', LedgerTransaction::TYPE_IN)
            ->where(function ($c) {
                $c->where('payment_method', 'collect_from_provider')->orWhereNull('booking_id');
            })
            ->whereNotNull('provider_id')
            ->groupBy('provider_id')
            ->pluck('id')
            ->filter();

        $lastByProvider = $lastCollectIds->isEmpty()
            ? collect()
            : LedgerTransaction::query()->whereIn('id', $lastCollectIds)->get()->keyBy('provider_id');

        $rows = [];
        foreach ($providers as $provider) {
            $balanceDue = $this->signedBalanceDue($provider);
            $last = $lastByProvider->get($provider->id);
            $categoryNames = $provider->subscribed_services
                ? $provider->subscribed_services->pluck('category.name')->filter()->unique()->values()->all()
                : [];
            $rows[] = [
                'provider_id' => (string) $provider->id,
                'provider_name' => (string) ($provider->company_name ?? ''),
                'category_label' => $categoryNames[0] ?? '—',
                'balance_due' => $balanceDue,
                'last_payment_amount' => $last ? (float) $last->amount : null,
                'last_payment_date' => $last?->date,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{balance_due: float}>  $rows
     * @return array{providers_owe_us: float, we_owe_providers: float, total_net: float}
     */
    public function summarizeBalances(array $rows): array
    {
        $providersOweUs = 0.0;
        $weOweProviders = 0.0;

        foreach ($rows as $row) {
            $balance = (float) ($row['balance_due'] ?? 0);
            if ($balance > 0.009) {
                $providersOweUs += $balance;
            } elseif ($balance < -0.009) {
                $weOweProviders += abs($balance);
            }
        }

        return [
            'providers_owe_us' => round($providersOweUs, 2),
            'we_owe_providers' => round($weOweProviders, 2),
            'total_net' => round($providersOweUs - $weOweProviders, 2),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function filterRowsByBalance(array $rows, string $balanceFilter): array
    {
        return match ($balanceFilter) {
            'positive' => array_values(array_filter(
                $rows,
                fn ($row) => (float) ($row['balance_due'] ?? 0) > 0.009
            )),
            'negative' => array_values(array_filter(
                $rows,
                fn ($row) => (float) ($row['balance_due'] ?? 0) < -0.009
            )),
            'zero' => array_values(array_filter(
                $rows,
                fn ($row) => abs((float) ($row['balance_due'] ?? 0)) <= 0.009
            )),
            default => $rows,
        };
    }

    /**
     * Signed Net balance: positive when provider owes company, negative when company owes provider.
     * Magnitude matches {@see provider_payment_net_balance_context()} display_amount.
     */
    protected function signedBalanceDue(Provider $provider): float
    {
        $providerId = (string) $provider->id;
        $net = (float) $this->settlementResolver->resolveForProviderId($providerId)['booking_settlement_net'];
        $context = provider_payment_net_balance_context(
            $providerId,
            (string) $provider->user_id,
            $net,
            (float) ($provider->owner?->account->account_receivable ?? 0),
            (float) ($provider->owner?->account->account_payable ?? 0),
        );

        $amount = round((float) $context['display_amount'], 2);
        if ($amount <= 0.009) {
            return 0.0;
        }

        if ($context['company_pays_provider']) {
            return -$amount;
        }

        return $amount;
    }
}
