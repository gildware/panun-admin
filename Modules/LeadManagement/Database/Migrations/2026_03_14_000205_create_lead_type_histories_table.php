<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('lead_type_histories')) {
            Schema::create('lead_type_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lead_id');
                $table->string('type', 32);
                $table->json('data')->nullable();
                $table->string('created_by', 64)->nullable();
                $table->timestamps();

                $table->foreign('lead_id')
                    ->references('id')
                    ->on('leads')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_type_histories');
    }
};

