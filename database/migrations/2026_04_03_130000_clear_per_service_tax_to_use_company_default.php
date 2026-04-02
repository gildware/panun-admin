<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * All services inherit category → subcategory → company tax unless an admin sets a service-level override again.
     */
    public function up(): void
    {
        DB::table('services')->update(['tax' => null, 'tax_label' => null]);
    }

    public function down(): void
    {
        // Historical per-service rates are not recoverable.
    }
};
