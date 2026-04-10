<?php

namespace Modules\TransactionModule\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
     * @return list<array{
     *     provider_id: string,
     *     provider_name: string,
     *     category_label: string,
     *     balance_due: float,
     *     last_payment_amount: ?float,
     *     last_payment_date: ?\Carbon\CarbonInterface
     * }>
     */
    public function buildRows(?string $search, ?string $categoryId, string $sort): array
    {
        $candidateIds = $this->candidateProviderIds();

        $query = Provider::query()
            ->whereIn('id', $candidateIds)
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
            $net = $this->settlementResolver->resolveForProviderId((string) $provider->id)['booking_settlement_net'];
            $payable = (float) ($provider->owner?->account->account_payable ?? 0);
            if (!$this->shouldInclude($net, $payable)) {
                continue;
            }
            $balanceDue = $this->rowBalanceDue($net, $payable);
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

        usort($rows, function ($a, $b) use ($sort) {
            return match ($sort) {
                'balance_asc' => $a['balance_due'] <=> $b['balance_due'],
                'name_asc' => strcmp((string) $a['provider_name'], (string) $b['provider_name']),
                default => $b['balance_due'] <=> $a['balance_due'],
            };
        });

        return $rows;
    }

    /**
     * @return list<string>
     */
    protected function candidateProviderIds(): array
    {
        $fromBookings = DB::table('bookings')->whereNotNull('provider_id')->distinct()->pluck('provider_id');
        $fromLedger = DB::table('ledger_transactions')->whereNotNull('provider_id')->distinct()->pluck('provider_id');
        $fromPayable = DB::table('providers')
            ->join('users', 'users.id', '=', 'providers.user_id')
            ->join('accounts', 'accounts.user_id', '=', 'users.id')
            ->where('accounts.account_payable', '>', 0.01)
            ->pluck('providers.id');

        return collect()
            ->merge($fromBookings)
            ->merge($fromLedger)
            ->merge($fromPayable)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function shouldInclude(float $net, float $payable): bool
    {
        return max(0.0, -$net) > 0.009 || $payable > 0.009;
    }

    protected function rowBalanceDue(float $net, float $payable): float
    {
        if (max(0.0, -$net) > 0.009) {
            return round(max(0.0, -$net), 2);
        }
        if ($payable > 0.009) {
            return round($payable, 2);
        }

        return 0.0;
    }
}
