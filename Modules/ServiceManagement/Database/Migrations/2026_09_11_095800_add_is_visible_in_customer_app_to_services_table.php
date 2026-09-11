<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'is_visible_in_customer_app')) {
            Schema::table('services', function (Blueprint $table) {
                $table->boolean('is_visible_in_customer_app')->default(true)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'is_visible_in_customer_app')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('is_visible_in_customer_app');
            });
        }
    }
};
