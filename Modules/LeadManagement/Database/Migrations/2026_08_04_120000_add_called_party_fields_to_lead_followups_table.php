<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lead_followups', function (Blueprint $table) {
            if (! Schema::hasColumn('lead_followups', 'called_party_type')) {
                $table->string('called_party_type', 16)->nullable()->after('contact_channel');
            }
            if (! Schema::hasColumn('lead_followups', 'called_name')) {
                $table->string('called_name')->nullable()->after('called_party_type');
            }
            if (! Schema::hasColumn('lead_followups', 'called_number')) {
                $table->string('called_number', 32)->nullable()->after('called_name');
            }
            if (! Schema::hasColumn('lead_followups', 'called_provider_id')) {
                $table->uuid('called_provider_id')->nullable()->after('called_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lead_followups', function (Blueprint $table) {
            $columns = [
                'called_party_type',
                'called_name',
                'called_number',
                'called_provider_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('lead_followups', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
