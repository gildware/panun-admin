<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people_leave_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 80);
            $table->string('accrual_type', 20);
            $table->decimal('casual_days', 6, 1)->default(0);
            $table->decimal('sick_days', 6, 1)->default(0);
            $table->decimal('earned_days', 6, 1)->default(0);
            $table->timestamps();
        });

        Schema::create('people_leave_grants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->unsignedSmallInteger('year');
            $table->string('leave_type', 20);
            $table->decimal('days', 6, 1);
            $table->string('source', 20);
            $table->string('period', 40);
            $table->string('note', 200)->nullable();
            $table->uuid('granted_by')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'leave_type', 'source', 'period'], 'people_leave_grants_period_unique');
            $table->index(['user_id', 'year']);
        });

        Schema::table('people_profiles', function (Blueprint $table) {
            $table->uuid('leave_policy_id')->nullable()->index();
            $table->date('leave_next_accrual_on')->nullable()->index();
        });

        Schema::table('people_leave_balances', function (Blueprint $table) {
            $table->decimal('casual_allowance', 6, 1)->default(0)->change();
            $table->decimal('sick_allowance', 6, 1)->default(0)->change();
            $table->decimal('earned_allowance', 6, 1)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('people_leave_balances', function (Blueprint $table) {
            $table->unsignedSmallInteger('casual_allowance')->default(8)->change();
            $table->unsignedSmallInteger('sick_allowance')->default(12)->change();
            $table->unsignedSmallInteger('earned_allowance')->default(15)->change();
        });

        Schema::table('people_profiles', function (Blueprint $table) {
            $table->dropColumn(['leave_policy_id', 'leave_next_accrual_on']);
        });

        Schema::dropIfExists('people_leave_grants');
        Schema::dropIfExists('people_leave_policies');
    }
};
