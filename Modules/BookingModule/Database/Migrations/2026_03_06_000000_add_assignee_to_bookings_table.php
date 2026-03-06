<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Assignee can be any admin/admin-employee user or null (unassigned)
            if (!Schema::hasColumn('bookings', 'assignee_id')) {
                $table->uuid('assignee_id')->nullable()->after('assigned_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'assignee_id')) {
                $table->dropColumn('assignee_id');
            }
        });
    }
};

