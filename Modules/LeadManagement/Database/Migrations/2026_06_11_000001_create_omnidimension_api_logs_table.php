<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('omnidimension_api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10);
            $table->string('path', 255);
            $table->json('query_params')->nullable();
            $table->longText('request_body')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->boolean('ok')->default(false);
            $table->string('error', 255)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->foreignUuid('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('omnidimension_api_logs');
    }
};
