<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'originated_from_booking_id')) {
                $table->foreignUuid('originated_from_booking_id')
                    ->nullable()
                    ->after('lead_id')
                    ->constrained('bookings')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'last_reopen_event_at')) {
                $table->timestamp('last_reopen_event_at')->nullable()->after('originated_from_booking_id');
            }
            if (!Schema::hasColumn('bookings', 'reopened_by')) {
                $table->foreignUuid('reopened_by')
                    ->nullable()
                    ->after('last_reopen_event_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        if (!Schema::hasTable('booking_reopen_events')) {
            Schema::create('booking_reopen_events', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('source_booking_id')->constrained('bookings')->cascadeOnDelete();
                $table->foreignUuid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('resolution', 32);
                $table->text('complaint_notes')->nullable();
                $table->foreignUuid('child_booking_id')->nullable()->constrained('bookings')->nullOnDelete();
                $table->string('target_status', 32)->nullable();
                $table->timestamps();

                $table->index(['source_booking_id', 'created_at']);
                $table->index('child_booking_id');
            });
        }

        if (Schema::hasTable('feedback_tag_configs')) {
            $now = now();
            DB::table('feedback_tag_configs')->updateOrInsert(
                [
                    'entity_type' => 'provider',
                    'feedback_type' => 'complaint',
                    'tag_key' => 'reopened',
                ],
                [
                    'label' => 'Reopened booking',
                    'score' => -3,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_reopen_events');

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'reopened_by')) {
                $table->dropForeign(['reopened_by']);
                $table->dropColumn('reopened_by');
            }
            if (Schema::hasColumn('bookings', 'last_reopen_event_at')) {
                $table->dropColumn('last_reopen_event_at');
            }
            if (Schema::hasColumn('bookings', 'originated_from_booking_id')) {
                $table->dropForeign(['originated_from_booking_id']);
                $table->dropColumn('originated_from_booking_id');
            }
        });

        if (Schema::hasTable('feedback_tag_configs')) {
            DB::table('feedback_tag_configs')
                ->where('entity_type', 'provider')
                ->where('feedback_type', 'complaint')
                ->where('tag_key', 'reopened')
                ->delete();
        }
    }
};
