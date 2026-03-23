<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL-compatible: set default only for newly inserted rows.
        // Existing rows keep their current values.
        DB::statement("ALTER TABLE providers ALTER provider_type SET DEFAULT 'individual'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE providers ALTER provider_type SET DEFAULT 'company'");
    }
};

