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
        Schema::create('service_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('variant_key', 191);
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['service_id', 'variant_key']);
            $table->index(['service_id', 'is_active']);
        });

        Schema::table('variations', function (Blueprint $table) {
            $table->foreignUuid('service_variant_id')->nullable()->after('service_id')
                ->constrained('service_variants')->cascadeOnDelete();
        });

        if (! Schema::hasTable('variations') || ! Schema::hasTable('service_variants')) {
            return;
        }

        $groups = DB::table('variations')
            ->select('service_id', 'variant_key', DB::raw('MIN(variant) as variant'))
            ->whereNotNull('variant_key')
            ->groupBy('service_id', 'variant_key')
            ->get();

        foreach ($groups as $group) {
            $variantKey = (string) $group->variant_key;
            if ($variantKey === '') {
                continue;
            }

            $id = (string) Str::uuid();
            DB::table('service_variants')->insert([
                'id' => $id,
                'service_id' => $group->service_id,
                'variant_key' => $variantKey,
                'title' => $group->variant ?: str_replace('-', ' ', $variantKey),
                'description' => null,
                'image' => null,
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('variations')
                ->where('service_id', $group->service_id)
                ->where('variant_key', $variantKey)
                ->update(['service_variant_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('variations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_variant_id');
        });

        Schema::dropIfExists('service_variants');
    }
};
