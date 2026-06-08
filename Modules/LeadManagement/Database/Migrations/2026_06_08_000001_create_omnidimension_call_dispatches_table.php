<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('omnidimension_call_dispatches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('omnidim_request_id')->nullable()->index();
            $table->unsignedBigInteger('omnidim_call_log_id')->nullable()->index();
            $table->string('to_number_e164', 20)->nullable();
            $table->json('call_context');
            $table->foreignUuid('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('omnidimension_call_dispatches');
    }
};
