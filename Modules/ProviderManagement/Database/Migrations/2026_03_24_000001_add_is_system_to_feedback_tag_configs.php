<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Built-in tag keys shipped with the product (not deletable). */
    private function defaultKeys(): array
    {
        return [
            ['provider', 'complaint', 'no_show'],
            ['provider', 'complaint', 'no_response'],
            ['provider', 'complaint', 'late_arrival'],
            ['provider', 'complaint', 'bad_behaviour'],
            ['provider', 'complaint', 'poor_service'],
            ['provider', 'positive_feedback', 'positive_feedback'],
            ['provider', 'positive_feedback', 'successful_job'],
            ['provider', 'non_complaint', 'provider_busy'],
            ['provider', 'non_complaint', 'customer_request'],
            ['provider', 'non_complaint', 'scheduling_issue'],
            ['provider', 'non_complaint', 'no_feedback'],
            ['customer', 'complaint', 'abusive_behaviour'],
            ['customer', 'complaint', 'no_response'],
            ['customer', 'complaint', 'payment_issue'],
            ['customer', 'complaint', 'last_minute_cancellation'],
            ['customer', 'positive_feedback', 'respectful'],
            ['customer', 'positive_feedback', 'on_time_payment'],
            ['customer', 'positive_feedback', 'clear_communication'],
            ['customer', 'non_complaint', 'no_feedback'],
        ];
    }

    public function up(): void
    {
        if (!Schema::hasTable('feedback_tag_configs')) {
            return;
        }

        if (!Schema::hasColumn('feedback_tag_configs', 'is_system')) {
            Schema::table('feedback_tag_configs', function (Blueprint $table) {
                $table->boolean('is_system')->default(false)->after('is_active');
            });
        }

        foreach ($this->defaultKeys() as [$entity, $feedbackType, $tagKey]) {
            DB::table('feedback_tag_configs')
                ->where('entity_type', $entity)
                ->where('feedback_type', $feedbackType)
                ->where('tag_key', $tagKey)
                ->update(['is_system' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('feedback_tag_configs') && Schema::hasColumn('feedback_tag_configs', 'is_system')) {
            Schema::table('feedback_tag_configs', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }
    }
};
