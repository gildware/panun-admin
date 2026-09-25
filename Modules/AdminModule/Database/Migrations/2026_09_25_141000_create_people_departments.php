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
        Schema::create('people_departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120)->unique();
            $table->timestamps();
        });

        if (! Schema::hasTable('people_profiles')) {
            return;
        }

        $now = now();
        $seen = [];
        foreach (DB::table('people_profiles')->where('department', '!=', '')->pluck('department') as $department) {
            $name = mb_substr(trim((string) $department), 0, 120);
            $key = mb_strtolower($name);
            if ($name === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            DB::table('people_departments')->insert([
                'id' => (string) Str::uuid(),
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('people_departments');
    }
};
