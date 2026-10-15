<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('people_profiles', 'employment_stage')) {
            Schema::table('people_profiles', function (Blueprint $table) {
                $table->string('employment_stage', 20)->default('permanent')->after('employment_status');
            });
        }

        if (! Schema::hasColumn('people_leave_policies', 'carry_limit')) {
            Schema::table('people_leave_policies', function (Blueprint $table) {
                $table->decimal('carry_limit', 6, 1)->default(0)->after('days');
            });
        }

        if (! Schema::hasColumn('people_leave_assignments', 'via_stage')) {
            Schema::table('people_leave_assignments', function (Blueprint $table) {
                $table->boolean('via_stage')->default(false)->after('via_department');
            });
        }

        if (! Schema::hasTable('people_stage_leave_policies')) {
            Schema::create('people_stage_leave_policies', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('employment_stage', 20);
                $table->uuid('leave_policy_id');
                $table->timestamps();
                $table->unique(['employment_stage', 'leave_policy_id'], 'people_stage_leave_policy_unique');
            });
        }

        if (Schema::hasTable('people_leave_requests') && ! Schema::hasColumn('people_leave_requests', 'year_split')) {
            Schema::table('people_leave_requests', function (Blueprint $table) {
                $table->decimal('days', 6, 1)->change();
                $table->json('year_split')->nullable()->after('days');
                $table->string('leave_type', 32)->change();
            });
        }

        if (Schema::hasTable('people_leave_grants') && Schema::hasColumn('people_leave_grants', 'leave_type')) {
            Schema::table('people_leave_grants', function (Blueprint $table) {
                $table->string('leave_type', 32)->change();
            });
        }

        if (Schema::hasColumn('people_profiles', 'employment_stage')) {
            DB::table('people_profiles')->where(function ($query) {
                $query->whereNull('employment_stage')->orWhere('employment_stage', '');
            })->update([
                'employment_stage' => 'permanent',
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('people_stage_leave_policies');

        Schema::table('people_leave_requests', function (Blueprint $table) {
            $table->dropColumn('year_split');
        });

        Schema::table('people_leave_assignments', function (Blueprint $table) {
            $table->dropColumn('via_stage');
        });

        Schema::table('people_leave_policies', function (Blueprint $table) {
            $table->dropColumn('carry_limit');
        });

        Schema::table('people_profiles', function (Blueprint $table) {
            $table->dropColumn('employment_stage');
        });
    }
};
