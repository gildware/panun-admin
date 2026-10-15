<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lead_future_provider_reasons')) {
            Schema::create('lead_future_provider_reasons', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $now = now();
        $reasons = [
            ['Not ready to join yet', 'Wants to become a provider, but not right now.'],
            ['Waiting for documents', 'Identity or business documents are still pending.'],
            ['Outside current zones', 'Can work, but not in a zone we cover yet.'],
            ['Will join next season', 'Asked to be contacted when the next season starts.'],
            ['Needs equipment first', 'Does not have the tools or vehicle needed yet.'],
        ];

        foreach ($reasons as [$name, $description]) {
            $exists = DB::table('lead_future_provider_reasons')->where('name', $name)->exists();
            if ($exists) {
                continue;
            }
            DB::table('lead_future_provider_reasons')->insert([
                'name' => $name,
                'description' => $description,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_future_provider_reasons');
    }
};
