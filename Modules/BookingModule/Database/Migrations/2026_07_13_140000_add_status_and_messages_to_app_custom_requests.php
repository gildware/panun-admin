<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\AppCustomRequest;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('app_custom_requests')) {
            DB::table('app_custom_requests')
                ->where('status', 'PENDING_REVIEW')
                ->update(['status' => AppCustomRequest::STATUS_PENDING]);

            DB::table('app_custom_requests')
                ->where('status', 'CONVERTED')
                ->update(['status' => AppCustomRequest::STATUS_ACCEPTED]);

            DB::table('app_custom_requests')
                ->where('status', 'CANCELLED')
                ->update(['status' => AppCustomRequest::STATUS_REJECTED]);
        }

        if (! Schema::hasTable('app_custom_request_messages')) {
            Schema::create('app_custom_request_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_custom_request_id')->constrained('app_custom_requests')->cascadeOnDelete();
                $table->string('sender_type', 20)->index();
                $table->uuid('sender_id')->nullable()->index();
                $table->text('message');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_custom_request_messages');
    }
};
