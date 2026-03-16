<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('lead_provider_checklist')) {
            return;
        }
        $indexName = 'lead_provider_checklist_lead_item_unique';
        $indexExists = DB::select("SHOW INDEX FROM lead_provider_checklist WHERE Key_name = ?", [$indexName]);
        if (empty($indexExists)) {
            Schema::table('lead_provider_checklist', function (Blueprint $table) use ($indexName) {
                $table->unique(['lead_id', 'provider_checklist_item_id'], $indexName);
            });
        }
    }

    public function down(): void
    {
        Schema::table('lead_provider_checklist', function (Blueprint $table) {
            $table->dropUnique('lead_provider_checklist_lead_item_unique');
        });
    }
};
