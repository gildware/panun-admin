<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->string('difficulty', 20)->default('standard')->after('version');
        });

        DB::table('training_programs')->where('name', 'Customer Support')->update([
            'difficulty' => 'foundation',
        ]);
    }

    public function down(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropColumn('difficulty');
        });
    }
};
