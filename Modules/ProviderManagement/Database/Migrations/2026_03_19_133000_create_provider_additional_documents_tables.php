<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('providers_additional_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('provider_id');
            $table->string('document_name', 191);
            $table->text('document_description')->nullable();
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('providers')->cascadeOnDelete();
        });

        Schema::create('providers_additional_document_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->string('file_path', 191);
            $table->string('storage', 50)->default('public');
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('providers_additional_documents')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers_additional_document_files');
        Schema::dropIfExists('providers_additional_documents');
    }
};

