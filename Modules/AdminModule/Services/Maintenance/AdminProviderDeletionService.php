<?php

namespace Modules\AdminModule\Services\Maintenance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AdminModule\Services\Maintenance\Concerns\AllowsLongMaintenanceRun;
use Modules\AdminModule\Services\Maintenance\Concerns\DeletesUserOperationalRelations;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Services\AdminBookingDeletionService;
use Modules\BusinessSettingsModule\Entities\Storage;
use Modules\PaymentModule\Entities\PaymentRequest;
use Modules\ProviderManagement\Entities\BankDetail;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderSetting;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\ReviewModule\Entities\Review;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\Serviceman;

class AdminProviderDeletionService
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

    public function countProviders(): int
    {
        return Provider::query()->withTrashed()->count();
    }

    /**
     * @return array{complete: bool, current: int, total: int, label: ?string}
     */
    public function deleteNextProvider(int $total, int $current): array
    {
        $provider = Provider::query()
            ->withTrashed()
            ->with(['owner', 'servicemen'])
            ->orderBy('id')
            ->first();

        if (! $provider) {
            return [
                'complete' => true,
                'current' => $current,
                'total' => $total,
                'label' => null,
            ];
        }

        $label = $provider->company_name ?: translate('Provider');

        DB::transaction(function () use ($provider) {
            $this->deleteProvider($provider);
        });

        $nextCurrent = $current + 1;
        $remaining = Provider::query()->withTrashed()->count();

        return [
            'complete' => $remaining === 0,
            'current' => $nextCurrent,
            'total' => $total,
            'label' => $label,
        ];
    }

    public function deleteAllProviders(): int
    {
        $deleted = 0;

        Provider::query()
            ->withTrashed()
            ->with(['owner', 'servicemen'])
            ->orderBy('id')
            ->chunkById(25, function ($providers) use (&$deleted) {
                foreach ($providers as $provider) {
                    DB::transaction(function () use ($provider) {
                        $this->deleteProvider($provider);
                    });
                    $deleted++;
                }
            });

        return $deleted;
    }

    public function deleteProvider(Provider $provider): void
    {
        $this->allowLongMaintenanceRun();

        $deletionService = app(AdminBookingDeletionService::class);
        $providerId = $provider->id;

        $this->removeProviderFiles($provider);

        $bookings = Booking::query()
            ->where('provider_id', $providerId)
            ->with(self::BOOKING_EAGER)
            ->get();

        foreach ($bookings as $booking) {
            $deletionService->deleteBookingAndRelations($booking);
        }

    if (Schema::hasColumn('bookings', 'provider_cancelled_by_provider_id')) {
      DB::table('bookings')
        ->where('provider_cancelled_by_provider_id', $providerId)
        ->update(['provider_cancelled_by_provider_id' => null]);
    }

    if (Schema::hasColumn('ledger_transactions', 'provider_id')) {
      LedgerTransaction::query()->where('provider_id', $providerId)->delete();
    }

    $relatedUserIds = [];
    foreach (Serviceman::query()->where('provider_id', $providerId)->get() as $serviceman) {
      $uid = $serviceman->user_id;
      if ($uid) {
        $relatedUserIds[] = $uid;
      }
      $servicemanTx = Transaction::query()->where(function ($q) use ($uid) {
        $q->where('from_user_id', $uid)->orWhere('to_user_id', $uid);
      })->get();
      $deletionService->reverseAccountsAndDeleteTransactions($servicemanTx);
      Account::query()->where('user_id', $uid)->delete();
      $serviceman->delete();
    }

    $ownerUserId = $provider->user_id;
    if ($ownerUserId) {
      $relatedUserIds[] = $ownerUserId;
      $ownerTx = Transaction::query()->where(function ($q) use ($ownerUserId) {
        $q->where('from_user_id', $ownerUserId)->orWhere('to_user_id', $ownerUserId);
      })->get();
      $deletionService->reverseAccountsAndDeleteTransactions($ownerTx);
      Account::query()->where('user_id', $ownerUserId)->delete();
    }

    $relatedUserIds = array_values(array_unique(array_filter($relatedUserIds)));
    $this->deleteChatDataForUsers($relatedUserIds);
    $this->deleteInAppCallsForUsers($relatedUserIds);
    $this->deleteMobileAppAiForUsers($relatedUserIds);

    foreach ($relatedUserIds as $userId) {
      $this->deleteRowsForUser('withdraw_requests', 'user_id', $userId);
      $this->deleteRowsForUser('user_notifications', 'user_id', $userId);
      $this->deleteRowsForUser('user_fcm_devices', 'user_id', $userId);
      $this->deleteRowsForUser('push_notification_users', 'user_id', $userId);
    }

    if (Schema::hasTable('subscription_subscriber_bookings')) {
      DB::table('subscription_subscriber_bookings')->where('provider_id', $providerId)->delete();
    }

    PaymentRequest::query()
      ->where('attribute', 'provider-reg')
      ->where('attribute_id', $providerId)
      ->delete();

    Review::query()->where('provider_id', $providerId)->delete();

    if (Schema::hasTable('provider_customer_reviews')) {
      DB::table('provider_customer_reviews')->where('provider_id', $providerId)->delete();
    }

    if (Schema::hasTable('favorite_providers')) {
      DB::table('favorite_providers')->where('provider_id', $providerId)->delete();
    }

    foreach (['package_subscriber_features', 'package_subscriber_limits', 'package_subscriber_logs', 'package_subscribers'] as $table) {
      if (Schema::hasTable($table)) {
        DB::table($table)->where('provider_id', $providerId)->delete();
      }
    }

    SubscribedService::query()->where('provider_id', $providerId)->delete();

    foreach ([
      'carts',
      'advertisements',
      'post_bids',
      'ignored_posts',
      'provider_notification_setups',
      'providers_withdraw_methods_data',
      'provider_zone',
      'provider_incidents',
      'provider_change_requests',
      'provider_showcase_items',
    ] as $table) {
      if (Schema::hasTable($table)) {
        DB::table($table)->where('provider_id', $providerId)->delete();
      }
    }

    if (Schema::hasTable('providers_additional_documents')) {
      $documentIds = DB::table('providers_additional_documents')
        ->where('provider_id', $providerId)
        ->pluck('id')
        ->all();
      if ($documentIds !== [] && Schema::hasTable('providers_additional_document_files')) {
        DB::table('providers_additional_document_files')->whereIn('document_id', $documentIds)->delete();
      }
      DB::table('providers_additional_documents')->where('provider_id', $providerId)->delete();
    }

    BankDetail::query()->where('provider_id', $providerId)->delete();
    ProviderSetting::query()->where('provider_id', $providerId)->delete();

    if (Schema::hasTable('provider_sub_category')) {
      DB::table('provider_sub_category')->where('provider_id', $providerId)->delete();
    }

    if (Schema::hasTable('storages')) {
      Storage::query()->where('model_id', $providerId)->where('model', Provider::class)->delete();
    }

    // Avoid stale eager-loaded relations in Provider::deleted (servicemen already removed above).
    $provider->unsetRelation('servicemen');
    $provider->unsetRelation('owner');

    $owner = $provider->owner()->withTrashed()->first();
    if ($owner) {
      $owner->forceDelete();
    }

    $provider->forceDelete();
  }

  private function removeProviderFiles(Provider $provider): void
  {
    file_remover('provider/logo/', $provider->logo);
    if (! empty($provider->cover_image)) {
      file_remover('provider/logo/', $provider->cover_image);
    }
    if (! empty($provider->contact_person_photo)) {
      file_remover('provider/contact_person_photo/', $provider->contact_person_photo);
    }
    if (! empty($provider->owner?->identification_image)) {
      foreach ($provider->owner->identification_image as $image) {
        $imgName = is_array($image) ? ($image['image'] ?? $image) : $image;
        file_remover('provider/identity/', $imgName);
      }
    }
    if (is_array($provider->company_identity_images)) {
      foreach ($provider->company_identity_images as $image) {
        $imgName = is_array($image) ? ($image['image'] ?? $image) : $image;
        file_remover('provider/company-identity/', $imgName);
      }
    }
  }
}
