<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_activity_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('employee_id')->index();
            $table->uuid('actor_id')->nullable()->index();
            $table->string('event_type', 64)->index();
            $table->string('subject_type', 64)->nullable();
            $table->string('subject_id', 191)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'event_type', 'created_at'], 'staff_activity_emp_type_created_idx');
            $table->index(['event_type', 'created_at'], 'staff_activity_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_activity_events');
    }
};
