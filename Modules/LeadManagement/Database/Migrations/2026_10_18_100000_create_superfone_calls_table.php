<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('superfone_calls')) {
            return;
        }

        Schema::create('superfone_calls', function (Blueprint $table) {
            $table->id();
            $table->string('cdr_uuid', 64)->unique();
            $table->unsignedBigInteger('cdr_id')->nullable();
            $table->string('cdr_request_uuid', 80)->nullable();
            $table->string('last_event', 64)->nullable();
            $table->string('direction', 16)->nullable();
            $table->string('disposition', 64)->nullable();
            $table->string('outcome', 16)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('ringing_seconds')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('answered_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->string('customer_phone', 32)->nullable();
            $table->string('customer_phone_digits', 20)->nullable();
            $table->string('superfone_number', 32)->nullable();
            $table->string('organization_name')->nullable();
            $table->string('staff_first_name')->nullable();
            $table->string('staff_last_name')->nullable();
            $table->string('staff_phone', 32)->nullable();
            $table->string('staff_name')->nullable();
            $table->string('employee_user_id', 64)->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('lead_followup_id')->nullable();
            $table->string('called_party_type', 32)->nullable();
            $table->text('recording_url')->nullable();
            $table->dateTime('recording_url_expires_at')->nullable();
            $table->dateTime('recording_file_expires_at')->nullable();
            $table->string('recording_path')->nullable();
            $table->string('recording_disk', 32)->nullable();
            $table->string('recording_mime', 128)->nullable();
            $table->string('recording_original_name')->nullable();
            $table->text('recording_error')->nullable();
            $table->longText('summary_text')->nullable();
            $table->json('summary_insights')->nullable();
            $table->json('payloads')->nullable();
            $table->timestamps();

            $table->index('customer_phone_digits');
            $table->index('employee_user_id');
            $table->index('started_at');
            $table->index('outcome');
            $table->index('lead_id');

            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('lead_followup_id')->references('id')->on('lead_followups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('superfone_calls');
    }
};
