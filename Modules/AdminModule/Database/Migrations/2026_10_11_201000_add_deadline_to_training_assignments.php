<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_assignments', function (Blueprint $table) {
            $table->date('due_at')->nullable()->after('assigned_by');
            $table->string('assigned_scope', 20)->default('employee')->after('due_at');
            $table->string('department_name', 120)->nullable()->after('assigned_scope');
        });
    }

    public function down(): void
    {
        Schema::table('training_assignments', function (Blueprint $table) {
            $table->dropColumn(['due_at', 'assigned_scope', 'department_name']);
        });
    }
};
