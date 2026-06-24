<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\PaymentModule\Entities\RazorpayWebhookLog;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('razorpay_webhook_logs', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->nullable()->after('booking_readable_id');
        });

        RazorpayWebhookLog::query()
            ->whereNull('paid_amount')
            ->orderBy('created_at')
            ->chunkById(100, function ($logs): void {
                foreach ($logs as $log) {
                    $entity = is_array($log->payload['payload']['payment']['entity'] ?? null)
                        ? $log->payload['payload']['payment']['entity']
                        : [];

                    if (! isset($entity['amount'])) {
                        continue;
                    }

                    $log->forceFill([
                        'paid_amount' => round((int) $entity['amount'] / 100, 2),
                    ])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('razorpay_webhook_logs', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });
    }
};
