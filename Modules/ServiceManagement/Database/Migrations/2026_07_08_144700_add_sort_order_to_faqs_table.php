<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ServiceManagement\Entities\Faq;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('faqs', 'sort_order')) {
            Schema::table('faqs', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            });
        } else {
            return;
        }

        $serviceIds = Faq::query()
            ->whereNotNull('service_id')
            ->distinct()
            ->pluck('service_id');

        foreach ($serviceIds as $serviceId) {
            $faqs = Faq::query()
                ->where('service_id', $serviceId)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['id']);

            foreach ($faqs as $index => $faq) {
                DB::table('faqs')->where('id', $faq->id)->update(['sort_order' => $index]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('faqs', 'sort_order')) {
            Schema::table('faqs', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
