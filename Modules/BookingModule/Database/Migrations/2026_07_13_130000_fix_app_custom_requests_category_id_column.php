<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('app_custom_requests')) {
            return;
        }

        Schema::table('app_custom_requests', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });

        Schema::table('app_custom_requests', function (Blueprint $table) {
            $table->uuid('category_id')->nullable()->index()->after('phone');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_custom_requests')) {
            return;
        }

        Schema::table('app_custom_requests', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });

        Schema::table('app_custom_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->index()->after('phone');
        });
    }
};
