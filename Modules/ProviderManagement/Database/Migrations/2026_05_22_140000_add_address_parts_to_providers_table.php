<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            if (! Schema::hasColumn('providers', 'street')) {
                $table->string('street', 191)->nullable()->after('company_address');
            }
            if (! Schema::hasColumn('providers', 'city')) {
                $table->string('city', 191)->nullable()->after('street');
            }
            if (! Schema::hasColumn('providers', 'pincode')) {
                $table->string('pincode', 32)->nullable()->after('city');
            }
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            if (Schema::hasColumn('providers', 'pincode')) {
                $table->dropColumn('pincode');
            }
            if (Schema::hasColumn('providers', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('providers', 'street')) {
                $table->dropColumn('street');
            }
        });
    }
};
