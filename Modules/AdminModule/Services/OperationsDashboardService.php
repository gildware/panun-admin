<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Services\CustomerPerformanceService;
use Modules\ProviderManagement\Services\ProviderPerformanceService;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserFcmDevice;

class OperationsDashboardService
{
    public function __construct(
        protected ProviderPerformanceService $providerPerformance,
        protected CustomerPerformanceService $customerPerformance,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return AdminDashboardCache::rememberMetrics('operations_dashboard:v2', function () {
            return [
                'summary' => $this->summary(),
                'top_customers' => $this->topCustomers(5),
                'top_providers' => $this->topProviders(5),
                'new_customers' => $this->newCustomers(5),
                'new_providers' => $this->newProviders(5),
                'recent_app_devices' => $this->recentAppDevices(5),
            ];
        });
    }

    /**
     * @return array<string, int>
     */
    private function summary(): array
    {
        $now = Carbon::now();

        return [
            'total_customers' => User::query()->inCustomerDirectory()->count(),
            'total_providers' => Provider::query()->where('is_approved', 1)->count(),
            'pending_providers' => Provider::query()->where('is_approved', 0)->count(),
            'total_services' => Service::query()->count(),
            'new_customers_this_month' => User::query()
                ->inCustomerDirectory()
                ->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->count(),
            'new_providers_this_month' => Provider::query()
                ->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->count(),
            'app_devices_total' => UserFcmDevice::query()
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->where('fcm_token', '!=', '@')
                ->count(),
            'app_devices_this_month' => UserFcmDevice::query()
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->where('fcm_token', '!=', '@')
                ->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->count(),
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function topCustomers(int $limit): Collection
    {
        $customers = User::query()
            ->inCustomerDirectory()
            ->withCount(['bookings as completed_bookings_count' => function ($query) {
                $query->where('booking_status', 'completed');
            }])
            ->having('completed_bookings_count', '>', 0)
            ->get();

        if ($customers->isEmpty()) {
            return collect();
        }

        $metrics = $this->customerPerformance->getAggregatedCustomerPerformanceMetrics(
            $customers->pluck('id')->all()
        );

        return $customers
            ->sort(function ($a, $b) use ($metrics) {
                $sa = (int) ($metrics->get($a->id)->performance_score ?? 0);
                $sb = (int) ($metrics->get($b->id)->performance_score ?? 0);
                if ($sa !== $sb) {
                    return $sb <=> $sa;
                }

                return ($b->completed_bookings_count ?? 0) <=> ($a->completed_bookings_count ?? 0);
            })
            ->values()
            ->take($limit)
            ->map(function ($customer) use ($metrics) {
                $customer->performance_score = (int) ($metrics->get($customer->id)->performance_score ?? 0);

                return $customer;
            });
    }

    /**
     * @return Collection<int, Provider>
     */
    private function topProviders(int $limit): Collection
    {
        $providers = Provider::query()
            ->with(['owner:id,first_name,last_name'])
            ->where('is_approved', 1)
            ->withCount(['bookings as completed_bookings_count' => function ($query) {
                $query->forRevenueReporting();
            }])
            ->having('completed_bookings_count', '>', 0)
            ->get();

        if ($providers->isEmpty()) {
            return collect();
        }

        $metrics = $this->providerPerformance->getAggregatedProviderPerformanceMetrics(
            $providers->pluck('id')->all()
        );

        return $providers
            ->sort(function ($a, $b) use ($metrics) {
                $sa = (int) ($metrics->get($a->id)->performance_score ?? 0);
                $sb = (int) ($metrics->get($b->id)->performance_score ?? 0);
                if ($sa !== $sb) {
                    return $sb <=> $sa;
                }

                return ($b->completed_bookings_count ?? 0) <=> ($a->completed_bookings_count ?? 0);
            })
            ->values()
            ->take($limit)
            ->map(function ($provider) use ($metrics) {
                $provider->performance_score = (int) ($metrics->get($provider->id)->performance_score ?? 0);

                return $provider;
            });
    }

    /**
     * @return Collection<int, User>
     */
    private function newCustomers(int $limit): Collection
    {
        return User::query()
            ->inCustomerDirectory()
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * @return Collection<int, Provider>
     */
    private function newProviders(int $limit): Collection
    {
        return Provider::query()
            ->with(['owner:id,first_name,last_name,phone'])
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * @return Collection<int, UserFcmDevice>
     */
    private function recentAppDevices(int $limit): Collection
    {
        return UserFcmDevice::query()
            ->with([
                'user' => function ($query) {
                    $query->withTrashed()
                        ->select('id', 'first_name', 'last_name', 'phone', 'user_type');
                },
                'user.provider:id,user_id,company_name,contact_person_name',
                'user.serviceman:id,user_id,provider_id',
                'user.serviceman.provider:id,company_name',
            ])
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->where('fcm_token', '!=', '@')
            ->latest()
            ->take($limit)
            ->get();
    }
}
