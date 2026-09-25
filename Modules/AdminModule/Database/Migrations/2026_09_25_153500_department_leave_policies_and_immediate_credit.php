<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people_department_leave_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('department_id');
            $table->uuid('leave_policy_id');
            $table->timestamps();
            $table->unique(['department_id', 'leave_policy_id'], 'people_dept_leave_policy_unique');
        });

        Schema::table('people_leave_assignments', function (Blueprint $table) {
            $table->boolean('via_employee')->default(false);
            $table->boolean('via_department')->default(false);
        });

        DB::table('people_leave_assignments')->update(['via_employee' => true]);
    }

    public function down(): void
    {
        Schema::table('people_leave_assignments', function (Blueprint $table) {
            $table->dropColumn(['via_employee', 'via_department']);
        });

        Schema::dropIfExists('people_department_leave_policies');
    }
};
