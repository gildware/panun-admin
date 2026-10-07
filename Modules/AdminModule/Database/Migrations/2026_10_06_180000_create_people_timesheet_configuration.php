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
        Schema::create('people_timesheet_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('people_timesheet_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('min_hours', 4, 1)->default(0);
            $table->json('week_off');
            $table->timestamps();
        });

        $now = now();
        DB::table('people_timesheet_settings')->insert([
            'id' => (string) Str::uuid(),
            'min_hours' => 0,
            'week_off' => json_encode(['sun']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('people_timesheet_tasks');
        Schema::dropIfExists('people_timesheet_settings');
    }
};
