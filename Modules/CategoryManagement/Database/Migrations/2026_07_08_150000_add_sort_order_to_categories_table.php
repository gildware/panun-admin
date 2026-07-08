<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('position');
        });

        // Main categories
        $mainIds = DB::table('categories')
            ->where('position', 1)
            ->orderBy('name')
            ->pluck('id');

        foreach ($mainIds as $index => $id) {
            DB::table('categories')->where('id', $id)->update(['sort_order' => $index]);
        }

        // Sub-categories per parent
        $parentIds = DB::table('categories')
            ->where('position', 2)
            ->whereNotNull('parent_id')
            ->distinct()
            ->pluck('parent_id');

        foreach ($parentIds as $parentId) {
            $subIds = DB::table('categories')
                ->where('position', 2)
                ->where('parent_id', $parentId)
                ->orderBy('name')
                ->pluck('id');

            foreach ($subIds as $index => $id) {
                DB::table('categories')->where('id', $id)->update(['sort_order' => $index]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
