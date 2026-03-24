<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            if (!Schema::hasColumn('providers', 'performance_status')) {
                $table->string('performance_status')->default('active')->index();
            }
            if (!Schema::hasColumn('providers', 'is_active_for_jobs')) {
                $table->boolean('is_active_for_jobs')->default(1)->index();
            }
        });

        if (!Schema::hasTable('provider_incidents')) {
            Schema::create('provider_incidents', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('provider_id')->constrained('providers')->cascadeOnDelete();
                $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
                $table->enum('incident_type', [
                    'NO_SHOW',
                    'UNRESPONSIVE',
                    'POOR_SERVICE',
                    'LATE_ARRIVAL',
                    'POSITIVE_FEEDBACK',
                    'SUCCESSFUL_JOB',
                ])->index();
                $table->text('notes')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['provider_id', 'created_at']);
                $table->index(['booking_id', 'provider_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_incidents');

        Schema::table('providers', function (Blueprint $table) {
            if (Schema::hasColumn('providers', 'performance_status')) {
                $table->dropColumn('performance_status');
            }
            if (Schema::hasColumn('providers', 'is_active_for_jobs')) {
                $table->dropColumn('is_active_for_jobs');
            }
        });
    }
};

