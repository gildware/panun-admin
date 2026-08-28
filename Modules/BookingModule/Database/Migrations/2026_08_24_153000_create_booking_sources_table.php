<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\BookingSource;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_sources')) {
            Schema::create('booking_sources', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $names = [];
        $names[strtolower(BookingSource::NAME_DIRECT_APP_BOOKING)] = [
            'name' => BookingSource::NAME_DIRECT_APP_BOOKING,
            'description' => 'Bookings placed directly by the customer in the mobile app.',
        ];

        if (Schema::hasColumn('bookings', 'booking_source')) {
            $fromBookings = DB::table('bookings')
                ->whereNotNull('booking_source')
                ->whereRaw("TRIM(booking_source) != ''")
                ->distinct()
                ->pluck('booking_source');

            foreach ($fromBookings as $raw) {
                $key = strtolower(trim((string) $raw));
                if ($key === '' || $key === 'app' || isset($names[$key])) {
                    continue;
                }
                $names[$key] = [
                    'name' => mb_convert_case(trim((string) $raw), MB_CASE_TITLE, 'UTF-8'),
                    'description' => null,
                ];
            }
        }

        $now = now();
        foreach ($names as $row) {
            $exists = DB::table('booking_sources')
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($row['name'])])
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('booking_sources')->insert([
                'name' => $row['name'],
                'description' => $row['description'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_sources');
    }
};
