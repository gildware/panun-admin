<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE people_salary_structures MODIFY effective_from CHAR(10) NOT NULL');

        DB::table('people_salary_structures')
            ->whereRaw('CHAR_LENGTH(effective_from) = 7')
            ->update(['effective_from' => DB::raw("CONCAT(effective_from, '-01')")]);
    }

    public function down(): void
    {
        DB::table('people_salary_structures')
            ->whereRaw('CHAR_LENGTH(effective_from) = 10')
            ->update(['effective_from' => DB::raw('LEFT(effective_from, 7)')]);

        DB::statement('ALTER TABLE people_salary_structures MODIFY effective_from CHAR(7) NOT NULL');
    }
};
