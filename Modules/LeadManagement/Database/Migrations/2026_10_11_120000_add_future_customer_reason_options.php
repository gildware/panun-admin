<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_future_customer_reasons') && ! Schema::hasColumn('lead_future_customer_reasons', 'followup_label')) {
            Schema::table('lead_future_customer_reasons', function (Blueprint $table) {
                $table->string('followup_label')->nullable()->after('description');
            });
        }

        if (Schema::hasTable('lead_future_customer_reason_options') && ! Schema::hasColumn('lead_future_customer_reason_options', 'name')) {
            Schema::drop('lead_future_customer_reason_options');
        }

        if (! Schema::hasTable('lead_future_customer_reason_options')) {
            Schema::create('lead_future_customer_reason_options', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lead_future_customer_reason_id');
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->foreign('lead_future_customer_reason_id', 'lfcr_options_reason_fk')
                    ->references('id')
                    ->on('lead_future_customer_reasons')
                    ->cascadeOnDelete();
            });
        }

        $now = now();
        $reasons = [
            [
                'name' => 'Looking for a service we don\'t provide yet',
                'description' => 'They want a service that is not on the catalog yet.',
                'followup_label' => 'Which service?',
                'also' => [],
                'options' => [
                    'Solar installation',
                    'CCTV and security',
                    'Interior design',
                    'Pest control',
                    'Water purifier',
                    'Appliance repair',
                ],
            ],
            [
                'name' => 'Looking for a service in an area we don\'t serve yet',
                'description' => 'They want a service, but the place is outside the areas we cover.',
                'followup_label' => 'Which area?',
                'also' => [],
                'options' => [
                    'Jammu',
                    'Sopore',
                    'Pulwama',
                    'Shopian',
                    'Kulgam',
                    'Kupwara',
                ],
            ],
            [
                'name' => 'Saved number for future use',
                'description' => 'The number was saved so we can contact them later.',
                'followup_label' => 'Saved for?',
                'also' => ['Saved Number for Future Use'],
                'options' => [
                    'Own future booking',
                    'Family member',
                    'Neighbour or friend',
                    'Not decided yet',
                ],
            ],
        ];

        foreach ($reasons as $reason) {
            $row = DB::table('lead_future_customer_reasons')->where('name', $reason['name'])->first();
            if (! $row) {
                foreach ($reason['also'] as $alias) {
                    $row = DB::table('lead_future_customer_reasons')->where('name', $alias)->first();
                    if ($row) {
                        break;
                    }
                }
            }

            if ($row) {
                DB::table('lead_future_customer_reasons')->where('id', $row->id)->update([
                    'name' => $reason['name'],
                    'description' => $row->description ?: $reason['description'],
                    'followup_label' => $reason['followup_label'],
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
                $reasonId = $row->id;
            } else {
                $reasonId = DB::table('lead_future_customer_reasons')->insertGetId([
                    'name' => $reason['name'],
                    'description' => $reason['description'],
                    'followup_label' => $reason['followup_label'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($reason['options'] as $optionName) {
                $exists = DB::table('lead_future_customer_reason_options')
                    ->where('lead_future_customer_reason_id', $reasonId)
                    ->where('name', $optionName)
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('lead_future_customer_reason_options')->insert([
                    'lead_future_customer_reason_id' => $reasonId,
                    'name' => $optionName,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_future_customer_reason_options');

        if (Schema::hasTable('lead_future_customer_reasons') && Schema::hasColumn('lead_future_customer_reasons', 'followup_label')) {
            Schema::table('lead_future_customer_reasons', function (Blueprint $table) {
                $table->dropColumn('followup_label');
            });
        }
    }
};
