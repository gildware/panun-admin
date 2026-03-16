<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('lead_followups')) {
            Schema::create('lead_followups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lead_id');
                $table->dateTime('followup_at');
                $table->text('remarks')->nullable();
                // created_by stores string/UUID user id
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
        Schema::dropIfExists('lead_followups');
    }
};

