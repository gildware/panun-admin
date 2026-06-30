<?php

namespace Modules\AdminModule\Services\Maintenance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AdminModule\Services\Maintenance\Concerns\AllowsLongMaintenanceRun;
use Modules\AdminModule\Services\Maintenance\Concerns\DeletesUserOperationalRelations;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Services\AdminBookingDeletionService;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;

class AdminCustomerDeletionService
{
    use AllowsLongMaintenanceRun;
    use DeletesUserOperationalRelations;

  private const BOOKING_EAGER = [
    'repeat.detail',
    'repeat.details_amounts',
    'repeat.statusHistories',
    'repeat.scheduleHistories',
    'repeat.repeatHistories',
    'detail',
    'details_amounts',
    'schedule_histories',
    'status_histories',
    'booking_offline_payments',
    'ignores',
    'reviews',
    'booking_partial_payments',
    'extra_services',
  ];

    public function countDeletableCustomers(): int
    {
        return User::query()
            ->inCustomerDirectory()
            ->withTrashed()
            ->whereDoesntHave('provider')
            ->count();
    }

    public function countSkippedCustomers(): int
    {
        return User::query()
            ->inCustomerDirectory()
            ->withTrashed()
            ->whereHas('provider')
            ->count();
    }

    /**
     * @return array{complete: bool, current: int, total: int, skipped: int, label: ?string}
     */
    public function deleteNextCustomer(int $total, int $current, int $skipped): array
    {
        $customer = User::query()
            ->inCustomerDirectory()
            ->withTrashed()
            ->whereDoesntHave('provider')
            ->orderBy('id')
            ->first();

        if (! $customer) {
            return [
                'complete' => true,
                'current' => $current,
                'total' => $total,
                'skipped' => $skipped,
                'label' => null,
            ];
        }

        $label = trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''));
        if ($label === '') {
            $label = $customer->phone ?: translate('Customer');
        }

        DB::transaction(function () use ($customer) {
            $this->deleteCustomer($customer);
        });

        $nextCurrent = $current + 1;
        $remaining = $this->countDeletableCustomers();

        return [
            'complete' => $remaining === 0,
            'current' => $nextCurrent,
            'total' => $total,
            'skipped' => $skipped,
            'label' => $label,
        ];
    }

    public function deleteAllCustomers(): array
    {
        $deleted = 0;
        $skipped = 0;

        User::query()
            ->inCustomerDirectory()
            ->withTrashed()
            ->orderBy('id')
            ->chunkById(50, function ($customers) use (&$deleted, &$skipped) {
                foreach ($customers as $customer) {
                    if ($customer->provider()->exists()) {
                        $skipped++;

                        continue;
                    }

                    DB::transaction(function () use ($customer) {
                        $this->deleteCustomer($customer);
                    });
                    $deleted++;
                }
            });

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    public function deleteCustomer(User $customer): void
    {
        $this->allowLongMaintenanceRun();

        $deletionService = app(AdminBookingDeletionService::class);
        $userId = $customer->id;

        $this->removeCustomerFiles($customer);

        $bookings = Booking::query()
            ->where('customer_id', $userId)
            ->with(self::BOOKING_EAGER)
            ->get();

        foreach ($bookings as $booking) {
            $deletionService->deleteBookingAndRelations($booking);
        }

    if (Schema::hasTable('booking_compensations')) {
      DB::table('booking_compensations')->where('customer_id', $userId)->delete();
    }

    $transactions = Transaction::query()->where(function ($q) use ($userId) {
      $q->where('from_user_id', $userId)->orWhere('to_user_id', $userId);
    })->get();
    $deletionService->reverseAccountsAndDeleteTransactions($transactions);
    Account::query()->where('user_id', $userId)->delete();

    $this->deleteCustomerPosts($userId);
    $this->deleteCustomerRelatedRows($userId);
    $this->deleteChatDataForUsers([$userId]);
    $this->deleteInAppCallsForUsers([$userId]);
    $this->deleteMobileAppAiForUsers([$userId]);

    $customer->forceDelete();
  }

  private function removeCustomerFiles(User $customer): void
  {
    file_remover('user/profile_image/', $customer->profile_image);
    foreach ((array) $customer->identification_image as $imageName) {
      file_remover('user/identity/', $imageName);
    }
  }

  private function deleteCustomerPosts(string $userId): void
  {
    if (! Schema::hasTable('posts')) {
      return;
    }

    $postIds = DB::table('posts')->where('customer_user_id', $userId)->pluck('id')->all();
    if ($postIds === []) {
      return;
    }

    foreach (['post_bids', 'ignored_posts', 'post_additional_information', 'post_additional_instructions'] as $table) {
      if (Schema::hasTable($table)) {
        DB::table($table)->whereIn('post_id', $postIds)->delete();
      }
    }

    DB::table('posts')->whereIn('id', $postIds)->delete();
  }

  private function deleteCustomerRelatedRows(string $userId): void
  {
    $specs = [
      ['user_addresses', 'user_id'],
      ['user_zones', 'user_id'],
      ['user_verifications', 'user_id'],
      ['loyalty_point_transactions', 'user_id'],
      ['carts', 'customer_id'],
      ['added_to_carts', 'user_id'],
      ['favorite_services', 'customer_user_id'],
      ['favorite_providers', 'customer_user_id'],
      ['coupon_customers', 'customer_user_id'],
      ['searched_data', 'user_id'],
      ['visited_services', 'user_id'],
      ['recent_views', 'user_id'],
      ['recent_searches', 'user_id'],
      ['service_requests', 'user_id'],
      ['customer_incidents', 'customer_id'],
      ['customer_cart_contacts', 'customer_id'],
      ['cart_service_infos', 'customer_id'],
      ['reviews', 'customer_id'],
      ['provider_customer_reviews', 'customer_id'],
      ['withdraw_requests', 'user_id'],
      ['user_notifications', 'user_id'],
      ['user_fcm_devices', 'user_id'],
      ['push_notification_users', 'user_id'],
      ['push_notification_delivery_logs', 'user_id'],
    ];

    foreach ($specs as [$table, $column]) {
      $this->deleteRowsForUser($table, $column, $userId);
    }
  }
}
