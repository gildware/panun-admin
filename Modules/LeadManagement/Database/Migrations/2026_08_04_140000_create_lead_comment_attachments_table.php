<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('lead_comment_attachments')) {
            return;
        }

        Schema::create('lead_comment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_comment_id')->constrained('lead_comments')->cascadeOnDelete();
            $table->string('uploaded_by', 64)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('stored_name', 255);
            $table->string('file_type', 128)->nullable();
            $table->string('disk', 32)->default('public');
            $table->timestamps();

            $table->index('lead_comment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_comment_attachments');
    }
};
