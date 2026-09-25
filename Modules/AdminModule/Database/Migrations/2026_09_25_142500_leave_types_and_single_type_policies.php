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
        Schema::create('people_leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 80);
            $table->string('code', 32)->unique();
            $table->boolean('tracks_balance')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        $now = now();
        $typeIds = [];
        foreach ([
            ['Casual', 'casual', true, 1],
            ['Sick', 'sick', true, 2],
            ['Earned', 'earned', true, 3],
            ['Unpaid', 'unpaid', false, 4],
        ] as [$name, $code, $tracks, $sort]) {
            $id = (string) Str::uuid();
            $typeIds[$code] = $id;
            DB::table('people_leave_types')->insert([
                'id' => $id,
                'name' => $name,
                'code' => $code,
                'tracks_balance' => $tracks,
                'sort' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('people_leave_policies', function (Blueprint $table) {
            $table->uuid('leave_type_id')->nullable()->after('name');
            $table->decimal('days', 6, 1)->default(0)->after('accrual_type');
        });

        Schema::create('people_leave_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('leave_policy_id');
            $table->date('next_accrual_on')->nullable()->index();
            $table->timestamps();
            $table->unique(['user_id', 'leave_policy_id']);
        });

        Schema::table('people_leave_balances', function (Blueprint $table) {
            $table->json('extra')->nullable();
        });

        $extraPolicies = [];
        if (Schema::hasColumn('people_leave_policies', 'casual_days')) {
            foreach (DB::table('people_leave_policies')->get() as $policy) {
                $parts = [];
                foreach (['casual', 'sick', 'earned'] as $code) {
                    $days = round((float) $policy->{$code.'_days'}, 1);
                    if ($days > 0) {
                        $parts[] = [$code, $days];
                    }
                }

                if ($parts === []) {
                    DB::table('people_leave_policies')->where('id', $policy->id)->update([
                        'leave_type_id' => $typeIds['casual'],
                        'days' => 0,
                    ]);

                    continue;
                }

                [$code, $days] = array_shift($parts);
                DB::table('people_leave_policies')->where('id', $policy->id)->update([
                    'leave_type_id' => $typeIds[$code],
                    'days' => $days,
                ]);

                foreach ($parts as [$extraCode, $extraDays]) {
                    $newId = (string) Str::uuid();
                    DB::table('people_leave_policies')->insert([
                        'id' => $newId,
                        'name' => mb_substr($policy->name.' · '.ucfirst($extraCode), 0, 80),
                        'leave_type_id' => $typeIds[$extraCode],
                        'accrual_type' => $policy->accrual_type,
                        'days' => $extraDays,
                        'casual_days' => 0,
                        'sick_days' => 0,
                        'earned_days' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $extraPolicies[$policy->id][] = $newId;
                }
            }
        }

        if (Schema::hasColumn('people_profiles', 'leave_policy_id')) {
            foreach (DB::table('people_profiles')->whereNotNull('leave_policy_id')->get() as $profile) {
                foreach (array_merge([$profile->leave_policy_id], $extraPolicies[$profile->leave_policy_id] ?? []) as $policyId) {
                    if (! DB::table('people_leave_policies')->where('id', $policyId)->exists()) {
                        continue;
                    }
                    DB::table('people_leave_assignments')->insert([
                        'id' => (string) Str::uuid(),
                        'user_id' => $profile->user_id,
                        'leave_policy_id' => $policyId,
                        'next_accrual_on' => $profile->leave_next_accrual_on,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        if (Schema::hasColumn('people_leave_policies', 'casual_days')) {
            Schema::table('people_leave_policies', function (Blueprint $table) {
                $table->dropColumn(['casual_days', 'sick_days', 'earned_days']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('people_leave_policies', function (Blueprint $table) {
            $table->decimal('casual_days', 6, 1)->default(0);
            $table->decimal('sick_days', 6, 1)->default(0);
            $table->decimal('earned_days', 6, 1)->default(0);
        });

        if (Schema::hasTable('people_leave_types') && Schema::hasColumn('people_leave_policies', 'days')) {
            $types = DB::table('people_leave_types')->pluck('code', 'id');
            foreach (DB::table('people_leave_policies')->get() as $policy) {
                $code = $types[$policy->leave_type_id] ?? null;
                if (in_array($code, ['casual', 'sick', 'earned'], true)) {
                    DB::table('people_leave_policies')->where('id', $policy->id)->update([
                        $code.'_days' => $policy->days,
                    ]);
                }
            }
        }

        Schema::table('people_leave_policies', function (Blueprint $table) {
            $table->dropColumn(['leave_type_id', 'days']);
        });

        Schema::table('people_leave_balances', function (Blueprint $table) {
            $table->dropColumn('extra');
        });

        Schema::dropIfExists('people_leave_assignments');
        Schema::dropIfExists('people_leave_types');
    }
};
