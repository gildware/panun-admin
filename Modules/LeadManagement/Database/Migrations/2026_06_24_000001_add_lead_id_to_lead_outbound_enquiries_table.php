<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_outbound_enquiries', function (Blueprint $table) {
            $table->foreignId('lead_id')
                ->nullable()
                ->after('id')
                ->constrained('leads')
                ->nullOnDelete();

            $table->index(['lead_id']);
        });
    }

    public function down(): void
    {
        Schema::table('lead_outbound_enquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lead_id');
        });
    }
};
