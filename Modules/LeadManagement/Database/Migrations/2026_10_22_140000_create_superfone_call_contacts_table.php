<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('superfone_call_contacts')) {
            return;
        }

        Schema::create('superfone_call_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('superfone_call_id')->constrained('superfone_calls')->cascadeOnDelete();
            $table->string('customer_phone_digits', 20)->nullable();
            $table->string('channel', 16);
            $table->text('remarks');
            $table->string('attachment_disk', 32)->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_mime', 128)->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->char('employee_user_id', 36)->nullable();
            $table->dateTime('contacted_at');
            $table->foreign('employee_user_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_phone_digits', 'contacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('superfone_call_contacts');
    }
};
