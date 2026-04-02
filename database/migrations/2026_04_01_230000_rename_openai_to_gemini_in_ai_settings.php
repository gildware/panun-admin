<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ai_settings')->where('ai_name', 'OpenAI')->update([
            'ai_name' => 'Gemini',
            'organization_id' => null,
        ]);
    }

    public function down(): void
    {
        DB::table('ai_settings')->where('ai_name', 'Gemini')->update([
            'ai_name' => 'OpenAI',
        ]);
    }
};
