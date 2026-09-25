<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people_profiles', function (Blueprint $table) {
            $table->string('department', 120)->default('');
            $table->string('employment_type', 20)->default('full_time');
            $table->date('date_of_birth')->nullable();
            $table->string('emergency_contact', 120)->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->string('bank_account', 40)->nullable();
            $table->string('bank_ifsc', 20)->nullable();
            $table->string('pan', 10)->nullable();
            $table->string('aadhaar', 12)->nullable();
            $table->string('uan', 20)->nullable();
            $table->string('esi_number', 20)->nullable();
            $table->string('employment_status', 20)->default('active');
            $table->date('last_working_day')->nullable();
        });

        Schema::table('people_documents', function (Blueprint $table) {
            $table->text('rejection_note')->nullable();
        });

        Schema::table('people_leave_requests', function (Blueprint $table) {
            $table->decimal('days', 4, 1)->change();
        });

        Schema::table('people_leave_balances', function (Blueprint $table) {
            $table->decimal('casual_used', 6, 1)->default(0)->change();
            $table->decimal('sick_used', 6, 1)->default(0)->change();
            $table->decimal('earned_used', 6, 1)->default(0)->change();
        });

        Schema::table('people_payslips', function (Blueprint $table) {
            $table->json('breakdown')->nullable();
            $table->decimal('lop_days', 5, 1)->default(0);
            $table->boolean('held')->default(false);
        });

        Schema::create('people_salary_structures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->char('effective_from', 7);
            $table->decimal('basic', 12, 2)->default(0);
            $table->decimal('hra', 12, 2)->default(0);
            $table->decimal('special_allowance', 12, 2)->default(0);
            $table->decimal('pf_employee', 12, 2)->default(0);
            $table->decimal('pf_employer', 12, 2)->default(0);
            $table->decimal('professional_tax', 12, 2)->default(0);
            $table->decimal('tds', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'effective_from']);
        });

        Schema::create('people_pay_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->char('period', 7);
            $table->string('label', 120);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->index(['user_id', 'period']);
        });

        Schema::create('people_payroll_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('period', 7)->unique();
            $table->string('status', 20)->default('draft');
            $table->boolean('attendance_locked')->default(false);
            $table->uuid('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people_payroll_runs');
        Schema::dropIfExists('people_pay_adjustments');
        Schema::dropIfExists('people_salary_structures');

        Schema::table('people_payslips', function (Blueprint $table) {
            $table->dropColumn(['breakdown', 'lop_days', 'held']);
        });

        Schema::table('people_documents', function (Blueprint $table) {
            $table->dropColumn('rejection_note');
        });

        Schema::table('people_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'department',
                'employment_type',
                'date_of_birth',
                'emergency_contact',
                'bank_name',
                'bank_account',
                'bank_ifsc',
                'pan',
                'aadhaar',
                'uan',
                'esi_number',
                'employment_status',
                'last_working_day',
            ]);
        });
    }
};
