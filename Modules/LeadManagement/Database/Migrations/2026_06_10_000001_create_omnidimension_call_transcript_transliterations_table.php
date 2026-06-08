<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('omnidimension_call_transcript_transliterations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('omnidim_call_log_id');
            $table->char('transcript_hash', 64);
            $table->longText('transliterated_transcript');
            $table->timestamps();

            $table->unique('omnidim_call_log_id', 'omni_call_translit_log_unique');
            $table->index(['omnidim_call_log_id', 'transcript_hash'], 'omni_call_translit_log_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('omnidimension_call_transcript_transliterations');
    }
};
