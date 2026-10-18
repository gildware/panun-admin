<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('superfone_api_logs')) {
            return;
        }

        Schema::create('superfone_api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 16);
            $table->string('kind', 32);
            $table->string('event', 64)->nullable();
            $table->string('cdr_uuid', 64)->nullable();
            $table->string('method', 8)->nullable();
            $table->text('url')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('result', 32);
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('result');
            $table->index('cdr_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('superfone_api_logs');
    }
};
