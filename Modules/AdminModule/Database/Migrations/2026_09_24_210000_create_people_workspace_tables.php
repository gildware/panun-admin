<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('employee_code', 20)->unique();
            $table->string('job_title', 120)->default('Employee');
            $table->string('work_location', 120)->default('');
            $table->text('address')->nullable();
            $table->uuid('manager_id')->nullable()->index();
            $table->date('joined_on')->nullable();
            $table->timestamps();
        });

        Schema::create('people_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('title', 80);
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('status', 20)->default('missing');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'title']);
        });

        Schema::create('people_leave_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('casual_allowance')->default(8);
            $table->unsignedSmallInteger('casual_used')->default(0);
            $table->unsignedSmallInteger('sick_allowance')->default(12);
            $table->unsignedSmallInteger('sick_used')->default(0);
            $table->unsignedSmallInteger('earned_allowance')->default(15);
            $table->unsignedSmallInteger('earned_used')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'year']);
        });

        Schema::create('people_leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('leave_type', 20);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedSmallInteger('days');
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->uuid('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('people_holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('holiday_on')->unique();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::create('people_payslips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->char('period', 7);
            $table->decimal('gross', 12, 2);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net', 12, 2);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'period']);
        });

        Schema::create('people_timesheets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->date('week_starts_on');
            $table->json('hours');
            $table->text('note')->nullable();
            $table->string('status', 20)->default('draft');
            $table->uuid('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'week_starts_on']);
        });

        $now = now();
        $holidays = [
            ['2026-01-26', 'Republic Day'],
            ['2026-08-15', 'Independence Day'],
            ['2026-10-02', 'Gandhi Jayanti'],
            ['2026-10-20', 'Dussehra'],
            ['2026-11-08', 'Diwali'],
            ['2026-12-25', 'Christmas'],
        ];

        foreach ($holidays as [$date, $name]) {
            DB::table('people_holidays')->insert([
                'id' => (string) Str::uuid(),
                'holiday_on' => $date,
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('people_timesheets');
        Schema::dropIfExists('people_payslips');
        Schema::dropIfExists('people_holidays');
        Schema::dropIfExists('people_leave_requests');
        Schema::dropIfExists('people_leave_balances');
        Schema::dropIfExists('people_documents');
        Schema::dropIfExists('people_profiles');
    }
};
