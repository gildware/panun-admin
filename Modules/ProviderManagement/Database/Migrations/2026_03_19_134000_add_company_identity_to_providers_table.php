<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('company_identity_type', 50)->nullable()->after('company_email');
            $table->string('company_identity_number', 191)->nullable()->after('company_identity_type');
            // Store uploaded identity files (json encoded array) similarly to users.identification_image.
            $table->string('company_identity_images', 191)->default(json_encode([]))->after('company_identity_number');
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('company_identity_images');
            $table->dropColumn('company_identity_number');
            $table->dropColumn('company_identity_type');
        });
    }
};

