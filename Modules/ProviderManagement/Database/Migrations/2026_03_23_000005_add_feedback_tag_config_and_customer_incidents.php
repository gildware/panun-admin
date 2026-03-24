<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('feedback_tag_configs')) {
            Schema::create('feedback_tag_configs', function (Blueprint $table) {
                $table->id();
                $table->enum('entity_type', ['provider', 'customer'])->index();
                $table->enum('feedback_type', ['complaint', 'positive_feedback', 'non_complaint'])->index();
                $table->string('tag_key', 64);
                $table->string('label', 120);
                $table->integer('score')->default(0);
                $table->boolean('is_active')->default(1)->index();
                $table->timestamps();

                $table->unique(['entity_type', 'feedback_type', 'tag_key'], 'feedback_tag_configs_unique_key');
            });
        }

        if (Schema::hasTable('provider_incidents') && !Schema::hasColumn('provider_incidents', 'score_delta')) {
            Schema::table('provider_incidents', function (Blueprint $table) {
                $table->integer('score_delta')->default(0)->after('tags');
                $table->index(['provider_id', 'action_type'], 'provider_incidents_provider_action_idx');
            });
        }

        if (!Schema::hasTable('customer_incidents')) {
            Schema::create('customer_incidents', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('customer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
                $table->string('action_type', 32)->index(); // completed/cancelled/provider_changed
                $table->enum('incident_type', ['COMPLAINT', 'POSITIVE_FEEDBACK', 'NON_COMPLAINT'])->index();
                $table->json('tags')->nullable();
                $table->integer('score_delta')->default(0);
                $table->text('notes')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['customer_id', 'created_at']);
                $table->index(['booking_id', 'customer_id']);
            });
        }

        $now = now();
        $defaults = [
            // Provider tags
            ['provider', 'complaint', 'no_show', 'No-show', -20, 1, $now, $now],
            ['provider', 'complaint', 'no_response', 'No response', -10, 1, $now, $now],
            ['provider', 'complaint', 'late_arrival', 'Late arrival', -5, 1, $now, $now],
            ['provider', 'complaint', 'bad_behaviour', 'Bad behaviour', -10, 1, $now, $now],
            ['provider', 'complaint', 'poor_service', 'Poor service', -15, 1, $now, $now],
            ['provider', 'positive_feedback', 'positive_feedback', 'Positive feedback', 10, 1, $now, $now],
            ['provider', 'positive_feedback', 'successful_job', 'Successful job', 5, 1, $now, $now],
            ['provider', 'non_complaint', 'provider_busy', 'Provider busy', 0, 1, $now, $now],
            ['provider', 'non_complaint', 'customer_request', 'Customer request', 0, 1, $now, $now],
            ['provider', 'non_complaint', 'scheduling_issue', 'Scheduling issue', 0, 1, $now, $now],
            ['provider', 'non_complaint', 'no_feedback', 'No feedback', 0, 1, $now, $now],

            // Customer tags (default suggestions; can be reconfigured)
            ['customer', 'complaint', 'abusive_behaviour', 'Abusive behaviour', -20, 1, $now, $now],
            ['customer', 'complaint', 'no_response', 'No response', -10, 1, $now, $now],
            ['customer', 'complaint', 'payment_issue', 'Payment issue', -15, 1, $now, $now],
            ['customer', 'complaint', 'last_minute_cancellation', 'Last minute cancellation', -10, 1, $now, $now],
            ['customer', 'positive_feedback', 'respectful', 'Respectful', 8, 1, $now, $now],
            ['customer', 'positive_feedback', 'on_time_payment', 'On-time payment', 10, 1, $now, $now],
            ['customer', 'positive_feedback', 'clear_communication', 'Clear communication', 5, 1, $now, $now],
            ['customer', 'non_complaint', 'no_feedback', 'No feedback', 0, 1, $now, $now],
        ];

        foreach ($defaults as $row) {
            DB::table('feedback_tag_configs')->updateOrInsert(
                [
                    'entity_type' => $row[0],
                    'feedback_type' => $row[1],
                    'tag_key' => $row[2],
                ],
                [
                    'label' => $row[3],
                    'score' => $row[4],
                    'is_active' => $row[5],
                    'created_at' => $row[6],
                    'updated_at' => $row[7],
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_incidents')) {
            Schema::dropIfExists('customer_incidents');
        }

        if (Schema::hasTable('provider_incidents') && Schema::hasColumn('provider_incidents', 'score_delta')) {
            Schema::table('provider_incidents', function (Blueprint $table) {
                $table->dropIndex('provider_incidents_provider_action_idx');
                $table->dropColumn('score_delta');
            });
        }

        Schema::dropIfExists('feedback_tag_configs');
    }
};

