<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people_leave_types', function (Blueprint $table) {
            $table->string('short_name', 12)->nullable()->after('name');
        });

        $known = [
            'casual' => 'CL',
            'sick' => 'SL',
            'earned' => 'PL',
            'unpaid' => 'LOP',
        ];

        $used = [];
        foreach (DB::table('people_leave_types')->orderBy('sort')->orderBy('name')->get() as $type) {
            $base = $known[$type->code] ?? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $type->name) ?: 'LV', 0, 3));
            $short = $base;
            $n = 2;
            while (isset($used[$short])) {
                $suffix = (string) $n;
                $short = substr($base, 0, 12 - strlen($suffix)).$suffix;
                $n++;
            }
            $used[$short] = true;
            DB::table('people_leave_types')->where('id', $type->id)->update([
                'short_name' => $short,
            ]);
        }

        Schema::table('people_leave_types', function (Blueprint $table) {
            $table->unique('short_name');
        });
    }

    public function down(): void
    {
        Schema::table('people_leave_types', function (Blueprint $table) {
            $table->dropUnique(['short_name']);
            $table->dropColumn('short_name');
        });
    }
};
