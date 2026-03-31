<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetail;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingReopenEvent;
use Modules\BookingModule\Services\BookingReopenService;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\UserManagement\Entities\User;

class SeedBookingTestMatrix extends Command
{
    protected $signature = 'booking:seed-test-matrix
                            {--fresh : Remove existing rows tagged [QA test matrix] before seeding}';

    protected $description = 'Create sample bookings across statuses (pending, accepted, ongoing, completed, canceled, refunded, partial payments, reopen open/resolved, follow-up child) for QA.';

    private const DESC_PREFIX = '[QA test matrix]';

    public function handle(): int
    {
        $ctx = $this->resolveContext();
        if ($ctx === null) {
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->wipeMatrixBookings();
        }

        $admin = $ctx['admin'];
        $customer = $ctx['customer'];
        $provider = $ctx['provider'];
        $zoneId = $ctx['zone_id'];
        $service = $ctx['service'];
        $variation = $ctx['variation'];

        $schedule = now()->addDays(5)->startOfHour();
        $base = [
            'customer_id' => $customer->id,
            'provider_id' => $provider->id,
            'zone_id' => $zoneId,
            'category_id' => $service->category_id,
            'sub_category_id' => $service->sub_category_id,
            'service_schedule' => $schedule,
            'payment_method' => 'cash',
            'total_tax_amount' => 0,
            'total_discount_amount' => 0,
            'booking_source' => 'admin',
        ];

        $created = [];

        DB::transaction(function () use ($base, $service, $variation, $admin, &$created) {
            $created['pending'] = $this->makeBooking($base, [
                'booking_status' => 'pending',
                'is_paid' => 0,
                'total_booking_amount' => 1500,
                'service_description' => self::DESC_PREFIX . ' pending (unpaid)',
            ], $service, $variation);

            $created['accepted'] = $this->makeBooking($base, [
                'booking_status' => 'accepted',
                'is_paid' => 1,
                'total_booking_amount' => 2200,
                'service_description' => self::DESC_PREFIX . ' accepted (paid cash)',
            ], $service, $variation);

            $created['ongoing'] = $this->makeBooking($base, [
                'booking_status' => 'ongoing',
                'is_paid' => 1,
                'total_booking_amount' => 1800,
                'service_description' => self::DESC_PREFIX . ' ongoing (paid)',
            ], $service, $variation);

            $created['completed'] = $this->makeBooking($base, [
                'booking_status' => 'completed',
                'is_paid' => 1,
                'total_booking_amount' => 3500,
                'service_description' => self::DESC_PREFIX . ' completed (paid)',
            ], $service, $variation);

            $created['canceled'] = $this->makeBooking($base, [
                'booking_status' => 'canceled',
                'is_paid' => 0,
                'total_booking_amount' => 900,
                'service_description' => self::DESC_PREFIX . ' canceled',
            ], $service, $variation);

            $created['refunded'] = $this->makeBooking($base, [
                'booking_status' => 'refunded',
                'is_paid' => 0,
                'total_booking_amount' => 1200,
                'service_description' => self::DESC_PREFIX . ' refunded',
            ], $service, $variation);

            $created['cash_after_pending'] = $this->makeBooking($base, [
                'booking_status' => 'pending',
                'is_paid' => 0,
                'payment_method' => 'cash_after_service',
                'is_verified' => 0,
                'total_booking_amount' => 800,
                'service_description' => self::DESC_PREFIX . ' pending cash_after_service (unverified)',
            ], $service, $variation);

            $bPartial = $this->makeBooking($base, [
                'booking_status' => 'accepted',
                'is_paid' => 0,
                'payment_method' => 'digital_payment',
                'total_booking_amount' => 600,
                'service_description' => self::DESC_PREFIX . ' partial payments (2 installments)',
            ], $service, $variation);
            BookingPartialPayment::query()->create([
                'booking_id' => $bPartial->id,
                'paid_with' => 'digital',
                'paid_amount' => 250,
                'due_amount' => 350,
                'received_by' => 'company',
            ]);
            BookingPartialPayment::query()->create([
                'booking_id' => $bPartial->id,
                'paid_with' => 'wallet',
                'paid_amount' => 350,
                'due_amount' => 0,
                'received_by' => 'company',
            ]);
            $created['partial_paid_full'] = $bPartial;

            $bPartialOpen = $this->makeBooking($base, [
                'booking_status' => 'accepted',
                'is_paid' => 0,
                'total_booking_amount' => 1000,
                'service_description' => self::DESC_PREFIX . ' partial payments (balance due)',
            ], $service, $variation);
            BookingPartialPayment::query()->create([
                'booking_id' => $bPartialOpen->id,
                'paid_with' => 'offline',
                'paid_amount' => 400,
                'due_amount' => 600,
                'received_by' => 'company',
            ]);
            $created['partial_balance_due'] = $bPartialOpen;

            $completedForReopen = $this->makeBooking($base, [
                'booking_status' => 'completed',
                'is_paid' => 1,
                'total_booking_amount' => 2750,
                'service_description' => self::DESC_PREFIX . ' completed → reopened in-place (open ticket)',
            ], $service, $variation);

            /** @var BookingReopenService $reopenService */
            $reopenService = app(BookingReopenService::class);
            $reopenService->reopenInPlace($completedForReopen->fresh(), $admin, 'QA seed: in-place reopen', 'accepted');
            $created['reopen_in_place_open'] = $completedForReopen->fresh();

            $completedResolvedFlow = $this->makeBooking($base, [
                'booking_status' => 'completed',
                'is_paid' => 1,
                'total_booking_amount' => 2650,
                'service_description' => self::DESC_PREFIX . ' reopen resolved (completed again + case closed)',
            ], $service, $variation);
            $reopenService->reopenInPlace($completedResolvedFlow->fresh(), $admin, 'QA seed: reopen then complete', 'accepted');
            $b = $completedResolvedFlow->fresh();
            $b->booking_status = 'completed';
            $b->save();
            $b->reopen_resolved_at = now();
            $b->reopen_resolved_by = $admin->id;
            $b->save();
            $created['reopen_resolved'] = $b->fresh();

            $completedForChild = $this->makeBooking($base, [
                'booking_status' => 'completed',
                'is_paid' => 1,
                'total_booking_amount' => 3100,
                'service_description' => self::DESC_PREFIX . ' parent completed (spawned follow-up booking)',
            ], $service, $variation);

            $child = $this->makeBooking($base, [
                'booking_status' => 'pending',
                'is_paid' => 0,
                'total_booking_amount' => 3100,
                'service_description' => self::DESC_PREFIX . ' follow-up from reopen (linked child)',
            ], $service, $variation);
            $reopenService->linkNewBookingFromReopenedCompleted(
                $completedForChild->fresh(),
                $child->fresh(),
                $admin,
                'QA seed: new booking from completed'
            );
            $created['reopen_new_booking_parent'] = $completedForChild->fresh();
            $created['reopen_new_booking_child'] = $child->fresh();
        });

        $this->info('Seeded QA matrix bookings. Filter admin list by description: ' . self::DESC_PREFIX);

        $rows = [];
        foreach ($created as $key => $booking) {
            $rows[] = [$key, $booking->readable_id, $booking->booking_status, Str::limit((string) $booking->service_description, 70)];
        }
        $this->table(['Key', 'Readable ID', 'Status', 'Description'], $rows);

        return self::SUCCESS;
    }

    /**
     * @return array{admin: User, customer: User, provider: Provider, zone_id: string, service: Service, variation: Variation}|null
     */
    private function resolveContext(): ?array
    {
        $admin = User::query()
            ->whereIn('user_type', ['super-admin', 'admin-employee'])
            ->orderBy('id')
            ->first();
        if (!$admin) {
            $this->error('No admin user (super-admin or admin-employee) found.');

            return null;
        }

        $customer = User::query()->where('user_type', 'customer')->orderBy('id')->first();
        if (!$customer) {
            $this->error('No customer user found.');

            return null;
        }

        $provider = Provider::query()
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNotNull('zone_id')
                    ->orWhereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('provider_zone')
                            ->whereColumn('provider_zone.provider_id', 'providers.id');
                    });
            })
            ->orderBy('id')
            ->first();
        if (!$provider) {
            $this->error('No active provider with a zone (zone_id or provider_zone) found.');

            return null;
        }

        $zoneIds = $provider->coveredLeafZoneIds();
        $zoneId = $zoneIds[0] ?? $provider->zone_id;
        if (!$zoneId) {
            $this->error('Could not resolve a zone_id for the provider.');

            return null;
        }

        $service = Service::query()
            ->whereNotNull('category_id')
            ->whereNotNull('sub_category_id')
            ->whereHas('variations')
            ->orderBy('id')
            ->first();
        if (!$service) {
            $this->error('No service with category, sub-category, and at least one variation found.');

            return null;
        }

        $variation = $service->variations()->orderBy('id')->first();
        if (!$variation || empty($variation->variant_key)) {
            $this->error('Service has no variation with variant_key.');

            return null;
        }

        return [
            'admin' => $admin,
            'customer' => $customer,
            'provider' => $provider,
            'zone_id' => (string) $zoneId,
            'service' => $service,
            'variation' => $variation,
        ];
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overrides
     */
    private function makeBooking(array $base, array $overrides, Service $service, Variation $variation): Booking
    {
        $booking = Booking::query()->create(array_merge($base, $overrides));

        $qty = 1;
        $unit = (float) $booking->total_booking_amount;
        BookingDetail::query()->create([
            'booking_id' => $booking->id,
            'service_id' => $service->id,
            'variant_key' => $variation->variant_key,
            'service_cost' => $unit,
            'quantity' => $qty,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_cost' => $unit * $qty,
        ]);

        return $booking->fresh();
    }

    private function wipeMatrixBookings(): void
    {
        $pattern = self::DESC_PREFIX . '%';

        while (Booking::query()->where('service_description', 'like', $pattern)->whereNotNull('originated_from_booking_id')->exists()) {
            Booking::query()
                ->where('service_description', 'like', $pattern)
                ->whereNotNull('originated_from_booking_id')
                ->orderBy('id')
                ->limit(50)
                ->get()
                ->each(fn (Booking $b) => $b->delete());
        }

        $ids = Booking::query()->where('service_description', 'like', $pattern)->pluck('id');
        if ($ids->isEmpty()) {
            $this->info('No prior QA matrix bookings to remove.');

            return;
        }

        BookingReopenEvent::query()
            ->where(function ($q) use ($ids) {
                $q->whereIn('source_booking_id', $ids)->orWhereIn('child_booking_id', $ids);
            })
            ->delete();
        BookingPartialPayment::query()->whereIn('booking_id', $ids)->delete();
        BookingDetail::query()->whereIn('booking_id', $ids)->delete();

        Booking::query()->whereIn('id', $ids)->each(fn (Booking $b) => $b->delete());

        $this->info('Removed prior QA matrix bookings.');
    }
}
