<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->decimal('tax_percentage', 8, 3)->nullable()->after('commission_tier_setup');
            $table->string('tax_label', 191)->nullable()->after('tax_percentage');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('tax_label', 191)->nullable()->after('tax');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->decimal('tax', 24, 3)->nullable()->default(null)->change();
        });

        BusinessSettings::updateOrCreate(
            ['key_name' => 'default_tax_label', 'settings_type' => 'business_information'],
            [
                'key_name' => 'default_tax_label',
                'live_values' => 'Tax',
                'test_values' => 'Tax',
                'settings_type' => 'business_information',
                'mode' => 'live',
                'is_active' => 1,
            ]
        );

        BusinessSettings::updateOrCreate(
            ['key_name' => 'default_tax_percentage', 'settings_type' => 'business_information'],
            [
                'key_name' => 'default_tax_percentage',
                'live_values' => '0',
                'test_values' => '0',
                'settings_type' => 'business_information',
                'mode' => 'live',
                'is_active' => 1,
            ]
        );
    }

    public function down(): void
    {
        BusinessSettings::where('key_name', 'default_tax_label')->where('settings_type', 'business_information')->delete();
        BusinessSettings::where('key_name', 'default_tax_percentage')->where('settings_type', 'business_information')->delete();

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('tax_label');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['tax_percentage', 'tax_label']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->decimal('tax', 24, 3)->default(0)->nullable(false)->change();
        });
    }
};
