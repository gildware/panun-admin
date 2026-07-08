<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        // Services under a sub-category
        $subCategoryIds = DB::table('services')
            ->whereNull('deleted_at')
            ->whereNotNull('sub_category_id')
            ->distinct()
            ->pluck('sub_category_id');

        foreach ($subCategoryIds as $subCategoryId) {
            $serviceIds = DB::table('services')
                ->whereNull('deleted_at')
                ->where('sub_category_id', $subCategoryId)
                ->orderBy('name')
                ->pluck('id');

            foreach ($serviceIds as $index => $id) {
                DB::table('services')->where('id', $id)->update(['sort_order' => $index]);
            }
        }

        // Direct services under main category (no subcategory)
        $categoryIds = DB::table('services')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('sub_category_id')->orWhere('sub_category_id', '');
            })
            ->whereNotNull('category_id')
            ->distinct()
            ->pluck('category_id');

        foreach ($categoryIds as $categoryId) {
            $serviceIds = DB::table('services')
                ->whereNull('deleted_at')
                ->where('category_id', $categoryId)
                ->where(function ($query) {
                    $query->whereNull('sub_category_id')->orWhere('sub_category_id', '');
                })
                ->orderBy('name')
                ->pluck('id');

            foreach ($serviceIds as $index => $id) {
                DB::table('services')->where('id', $id)->update(['sort_order' => $index]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
