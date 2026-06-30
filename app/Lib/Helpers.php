<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\SubscriptionSubscriberBooking;
use Modules\BusinessSettingsModule\Entities\NotificationSetup;
use Modules\BusinessSettingsModule\Entities\PackageSubscriber;
use Modules\BusinessSettingsModule\Entities\SettingsTutorials;
use Modules\PaymentModule\Entities\Bonus;
use Modules\PaymentModule\Entities\Setting;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\UserManagement\Entities\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\UploadedFile;

if (!function_exists('admin_uses_top_nav')) {
    /**
     * When true, admin uses the top navigation chrome instead of sidebar + legacy header.
     * Rollback: set ADMIN_TOP_NAV=false in .env and php artisan config:clear
     */
    function admin_uses_top_nav(): bool
    {
        return filter_var(config('admin.top_nav', false), FILTER_VALIDATE_BOOLEAN);
    }
}

if (!function_exists('admin_uses_partial_nav')) {
    function admin_uses_partial_nav(): bool
    {
        return admin_uses_top_nav()
            && filter_var(config('admin.partial_nav', true), FILTER_VALIDATE_BOOLEAN);
    }
}

if (!function_exists('admin_nav_placeholder')) {
    function admin_nav_placeholder(string $type = 'image'): string
    {
        return match ($type) {
            'logo' => asset('assets/admin-module/img/placeholder.png'),
            'profile' => asset('assets/admin-module/img/customer.png'),
            default => asset('assets/placeholder.png'),
        };
    }
}

if (!function_exists('admin_nav_image_src')) {
    function admin_nav_image_src(?string $src, string $placeholderType = 'image'): string
    {
        $src = trim((string) $src);

        if ($src === '' || $src === 'null' || str_ends_with($src, '/null')) {
            return admin_nav_placeholder($placeholderType);
        }

        return $src;
    }
}

if (!function_exists('use_dummy_login_otp')) {
    /**
     * When true, login OTP is a fixed code (default 123456) for customer/provider apps.
     */
    function use_dummy_login_otp(): bool
    {
        return filter_var(config('app.use_dummy_otp', false), FILTER_VALIDATE_BOOLEAN);
    }
}

if (!function_exists('generate_login_otp')) {
    function generate_login_otp(): string
    {
        if (use_dummy_login_otp()) {
            return (string) config('app.dummy_login_otp', '123456');
        }

        return (string) rand(100000, 999999);
    }
}

if (!function_exists('api_user')) {
    /**
     * Resolve the authenticated API user without throwing when the client sends
     * Authorization: Bearer null (common for guest sessions in mobile apps).
     */
    function api_user(): ?User
    {
        $token = request()->bearerToken();

        if ($token === null || $token === '' || in_array($token, ['null', 'undefined'], true)) {
            return null;
        }

        try {
            return auth('api')->user();
        } catch (\Throwable) {
            return null;
        }
    }
}

if (!function_exists('format_relative_time_ago')) {
    /**
     * Human-readable relative time without decimals.
     * e.g. "6 minutes ago", "1 hour 30 minutes ago", "2 days ago"
     */
    function format_relative_time_ago($datetime): string
    {
        $createdAt = $datetime instanceof Carbon ? $datetime : Carbon::parse($datetime);
        $totalMinutes = (int) round(abs($createdAt->diffInMinutes(Carbon::now())));

        if ($totalMinutes < 1) {
            $totalMinutes = 1;
        }

        $totalDays = intdiv($totalMinutes, 1440);
        $remainingMinutes = $totalMinutes % 1440;
        $hours = intdiv($remainingMinutes, 60);
        $minutes = $remainingMinutes % 60;

        if ($totalDays > 0) {
            $unit = translate($totalDays === 1 ? 'day' : 'days');

            return $totalDays.' '.$unit.' '.translate('ago');
        }

        if ($hours > 0) {
            $time = $hours.' '.translate('hours');
            if ($minutes > 0) {
                $time .= ' '.$minutes.' '.translate('minutes');
            }

            return $time.' '.translate('ago');
        }

        return $totalMinutes.' '.translate('minutes').' '.translate('ago');
    }
}

if (!function_exists('translate')) {
    function translate($key)
    {
        static $langArrays = [];

        try {
            $local = app()->getLocale();
            if (! isset($langArrays[$local])) {
                $langArrays[$local] = include base_path('resources/lang/'.$local.'/lang.php');
            }
            $lang_array = &$langArrays[$local];
            $processed_key = ucfirst(str_replace('_', ' ', str_ireplace(['\'', '"', ';', '<', '>', '?'], ' ', $key)));
            if (! array_key_exists($key, $lang_array)) {
                $lang_array[$key] = $processed_key;
                $str = "<?php return ".var_export($lang_array, true).';';
                file_put_contents(base_path('resources/lang/'.$local.'/lang.php'), $str);
                $result = $processed_key;
            } else {
                $result = $lang_array[$key];
            }

            return $result;
        } catch (\Exception $exception) {
            return $key;
        }
    }
}

if (!function_exists('bs_data')) {
    function bs_data($settings, $key, $required = 0)
    {
        try {
            if (env('APP_ENV') == 'local' || env('APP_ENV') == 'live' || $required) {
                $config = $settings->where('key_name', $key)->first()->live_values;
            } else {
                $config = null;
            }

        } catch (Exception $exception) {
            return null;
        }

        return (isset($config)) ? $config : null;
    }
}

if (!function_exists('bs_data_text')) {
    function bs_data_text($settings, $key, $required = 0)
    {
        try {
            if (env('APP_ENV') == 'local' || env('APP_ENV') == 'live' || $required) {
                $config = $settings->where('key', $key)->first()->value;
            } else {
                $config = null;
            }

        } catch (Exception $exception) {
            return null;
        }

        return (isset($config)) ? $config : null;
    }
}

if (!function_exists('error_processor')) {
    function error_processor($validator)
    {
        $errors = [];
        foreach ($validator->errors()->getMessages() as $index => $error) {
            $errors[] = ['error_code' => $index, 'message' => translate($error[0])];
        }
        return $errors;
    }
}

if (!function_exists('get_path')) {
    function get_path($type)
    {
        if ($type == 'public') {
            return url('/') . '/public';
        }

        return url('/');
    }
}

if (!function_exists('response_formatter')) {
    function response_formatter($constant, array|object|null $content = null, $errors = []): array
    {
        $constant = [
            'response_code' => $constant['response_code'],
            'message' => translate($constant['message']),
        ];
        $constant['content'] = $content;
        $constant['errors'] = $errors;

        return $constant;
    }
}

if (!function_exists('getDisk')) {
    function getDisk()
    {
        static $resolved = null;
        static $cacheKey = null;

        $storageType = business_config('storage_connection_type', 'storage_settings');
        $key = (string) ($storageType->live_values ?? 'local');

        if ($resolved !== null && $cacheKey === $key) {
            return $resolved;
        }

        $cacheKey = $key;
        $resolved = isset($storageType) ? ($storageType->live_values == 's3' ? 's3' : 'public') : 'public';

        return $resolved;
    }
}

if (!function_exists('file_uploader')) {
//    function file_uploader(string $dir, string $format, array|object|null $image = null, ?string $old_image = null)
//    {
//        if ($image == null) return $old_image ?? 'def.png';
//
//        if (isset($old_image)) Storage::disk(getDisk())->delete($dir . $old_image);
//
//        $imageName = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
//
//        try {
//            if (!Storage::disk(getDisk())->exists($dir)) {
//                Storage::disk(getDisk())->makeDirectory($dir);
//            }
//            Storage::disk(getDisk())->put($dir . $imageName, file_get_contents($image));
//        }catch (Exception $exception){
//            if (getDisk() == 's3'){
//                Toastr::error(translate('Image upload failed. Please check S3 credentials.'));
//                return $old_image ?? 'def.png';
//            }
//        }
//        return $imageName;
//    }

    function file_uploader(string $dir, string $format, array|object|null $image = null, ?string $old_image = null)
    {
        if ($image == null) {
            return $old_image ?? 'def.png';
        }

        $disk = getDisk();
        $dir  = \App\Support\StoragePathPrefix::apply(rtrim($dir, '/') . '/');

        // Do not delete $old_image until a new file is stored successfully; otherwise a failed
        // resize, S3 put, or non-image upload leaves the DB filename pointing at a removed file.

        /**
         * 🚫 If the file is NOT an image → upload normally (PDF, Doc, Zip, etc.)
         */
        if (!str_starts_with($image->getMimeType(), 'image/')) {

            $imageName = now()->toDateString() . "-" . uniqid() . "." . $format;

            try {
                if (!Storage::disk($disk)->exists($dir)) {
                    Storage::disk($disk)->makeDirectory($dir);
                }
                Storage::disk($disk)->put($dir . $imageName, file_get_contents($image));
            } catch (\Exception $exception) {
                if ($disk == 's3') {
                    Toastr::error(translate('File upload failed. Please check S3 credentials.'));
                }
                return $old_image ?? 'def.png';
            }

            if ($old_image) {
                foreach (\App\Support\StoragePathPrefix::keyVariants($dir.$old_image) as $deleteKey) {
                    try {
                        Storage::disk($disk)->delete($deleteKey);
                    } catch (\Throwable $e) {
                        //
                    }
                }
            }

            return $imageName; // RETURN HERE ✔
        }

        /**
         * If the file IS an image → process + convert
         */
        $sourcePath = $image instanceof \Illuminate\Http\UploadedFile
            ? $image->getRealPath()
            : $image;

        $info = @getimagesize($sourcePath);
        if (!$info || empty($info['mime'])) {
            return $old_image ?? 'def.png';
        }

        $mime = strtolower($info['mime']);

        $format = match ($mime) {
            'image/webp' => 'webp',
            'image/gif'  => 'gif', // don't break animations
            default      => $format,
        };

        $imageName = now()->toDateString() . "-" . uniqid() . "." . $format;
        $savePath = upload_processing_temp_path($disk, $dir, $imageName);

        // Ensure folder exists on the target disk (no-op for S3/R2).
        if (! Storage::disk($disk)->exists($dir)) {
            Storage::disk($disk)->makeDirectory($dir);
        }

        /**
         *  GIF & already-WEBP → copy only (no convert)
         */
        if ($mime === 'image/gif' || ($mime === 'image/webp' && $format === 'webp')) {
            if (! copy($sourcePath, $savePath)) {
                return $old_image ?? 'def.png';
            }
            try {
                Storage::disk($disk)->put($dir . $imageName, file_get_contents($savePath));
            } catch (\Exception $exception) {
                if ($disk == 's3') {
                    Toastr::error(translate('Image upload failed. Please check S3 credentials.'));
                }

                return $old_image ?? 'def.png';
            } finally {
                upload_processing_temp_cleanup($savePath, $disk);
            }
            if ($old_image) {
                foreach (\App\Support\StoragePathPrefix::keyVariants($dir.$old_image) as $deleteKey) {
                    try {
                        Storage::disk($disk)->delete($deleteKey);
                    } catch (\Throwable $e) {
                        //
                    }
                }
            }

            return $imageName;
        }

        /**
         * Convert other images to GD for processing
         */
        $gdImage = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png'  => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default      => null
        };

        if (!$gdImage) return $old_image ?? 'def.png';

        if (in_array($mime, ['image/png', 'image/webp'])) {
            imagealphablending($gdImage, false);
            imagesavealpha($gdImage, true);
        }

        /**
         *  Resize if too large
         */
        $maxSize = 2500;
        $w = imagesx($gdImage);
        $h = imagesy($gdImage);

        if ($w > $maxSize || $h > $maxSize) {
            $ratio = min($maxSize / $w, $maxSize / $h);
            $nw = (int)($w * $ratio);
            $nh = (int)($h * $ratio);

            $temp = imagecreatetruecolor($nw, $nh);
            imagealphablending($temp, false);
            imagesavealpha($temp, true);

            imagecopyresampled($temp, $gdImage, 0, 0, 0, 0, $nw, $nh, $w, $h);

            imagedestroy($gdImage);
            $gdImage = $temp;
        }

        /**
         *  Save final image (convert to webp/png/jpg)
         */
        $saved = match ($format) {
            'jpg','jpeg' => imagejpeg($gdImage, $savePath, 85),
            'png'        => imagepng($gdImage, $savePath, -1),
            'webp'       => imagewebp($gdImage, $savePath, 78),
            default      => false,
        };

        imagedestroy($gdImage);

        if (! $saved) {
            upload_processing_temp_cleanup($savePath, $disk);
            if ($disk == 's3') {
                Toastr::error(translate('Image upload failed. Please check S3 credentials.'));
            }

            return $old_image ?? 'def.png';
        }

        try {
            Storage::disk($disk)->put($dir . $imageName, file_get_contents($savePath));
        } catch (\Exception $exception) {
            if ($disk == 's3') {
                Toastr::error(translate('Image upload failed. Please check S3 credentials.'));
            }

            return $old_image ?? 'def.png';
        } finally {
            upload_processing_temp_cleanup($savePath, $disk);
        }

        if ($old_image) {
            foreach (\App\Support\StoragePathPrefix::keyVariants($dir.$old_image) as $deleteKey) {
                try {
                    Storage::disk($disk)->delete($deleteKey);
                } catch (\Throwable $e) {
                    //
                }
            }
        }

        return $imageName; // FINAL RETURN ✔
    }

}

if (!function_exists('upload_processing_temp_path')) {
    /**
     * Local path used while resizing/converting before put() to public or S3/R2 disk.
     */
    function upload_processing_temp_path(string $disk, string $dir, string $imageName): string
    {
        if ($disk === 's3') {
            $tempDir = storage_path('app/temp/uploads');
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            return $tempDir.'/'.$imageName;
        }

        $localDir = storage_path('app/public/'.rtrim($dir, '/').'/');
        if (! is_dir($localDir)) {
            mkdir($localDir, 0755, true);
        }

        return $localDir.$imageName;
    }
}

if (!function_exists('upload_processing_temp_cleanup')) {
    function upload_processing_temp_cleanup(string $savePath, string $disk): void
    {
        if ($disk !== 's3' || ! is_file($savePath)) {
            return;
        }

        @unlink($savePath);
    }
}

if (!function_exists('resolve_stored_media_key')) {
    /**
     * DB may store a full key (category/home-appliances/file.webp) or legacy filename only.
     */
    function resolve_stored_media_key(?string $stored, string $legacyPrefix): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }

        $stored = ltrim($stored, '/');

        if (str_contains($stored, '/')) {
            return $stored;
        }

        return rtrim($legacyPrefix, '/').'/'.$stored;
    }
}

if (!function_exists('media_storage_delete')) {
    function media_storage_delete(?string $stored): void
    {
        if ($stored === null || $stored === '') {
            return;
        }

        $stored = ltrim($stored, '/');
        $primaryDisk = function_exists('getDisk') ? getDisk() : 'public';

        foreach (\App\Support\StoragePathPrefix::keyVariants($stored) as $key) {
            foreach (array_unique([$primaryDisk, 'public', 's3']) as $disk) {
                try {
                    if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($key)) {
                        \Illuminate\Support\Facades\Storage::disk($disk)->delete($key);
                    }
                } catch (\Throwable $e) {
                    //
                }
            }
        }
    }
}

if (!function_exists('media_file_uploader')) {
    /**
     * Upload to $dir and return the full storage key (dir + filename).
     */
    function media_file_uploader(string $dir, string $format, array|object|null $image = null, ?string $stored = null): string
    {
        $dir = rtrim($dir, '/').'/';
        $oldFilename = null;

        if ($stored) {
            if (str_contains($stored, '/')) {
                media_storage_delete($stored);
            } else {
                $oldFilename = $stored;
            }
        }

        $filename = file_uploader($dir, $format, $image, $oldFilename);

        return $dir.$filename;
    }
}

if (!function_exists('advertisement_media_uploader')) {
    function advertisement_media_uploader($file, mixed $providerOrAdvertisement = null, ?string $stored = null): string
    {
        $provider = null;
        if ($providerOrAdvertisement instanceof \Modules\ProviderManagement\Entities\Provider) {
            $provider = $providerOrAdvertisement;
        } elseif (is_object($providerOrAdvertisement) && ! empty($providerOrAdvertisement->provider_id)) {
            $provider = \Modules\ProviderManagement\Entities\Provider::find($providerOrAdvertisement->provider_id);
        }

        return media_file_uploader(
            \App\Support\MediaStoragePath::advertisementDir($provider),
            $file->getClientOriginalExtension(),
            $file,
            $stored
        );
    }
}

if (!function_exists('file_remover')) {
    function file_remover(string $dir, $image): bool
    {
        if (! isset($image)) {
            return true;
        }

        if (is_array($image)) {
            foreach ($image as $img) {
                file_remover($dir, $img);
            }

            return true;
        }

        $dir = rtrim($dir, '/').'/';
        $primaryDisk = function_exists('getDisk') ? getDisk() : 'public';

        $key = str_contains((string) $image, '/')
            ? ltrim((string) $image, '/')
            : $dir.$image;

        foreach (\App\Support\StoragePathPrefix::keyVariants($key) as $variant) {
            foreach (array_unique([$primaryDisk, 'public', 's3']) as $disk) {
                try {
                    if (Storage::disk($disk)->exists($variant)) {
                        Storage::disk($disk)->delete($variant);
                    }
                } catch (\Throwable $e) {
                    //
                }
            }
        }

        return true;
    }
}

if (!function_exists('divnum')) {
    function divnum($numerator, $denominator)
    {
        return $denominator == 0 ? 0 : ($numerator / $denominator);
    }
}

if (!function_exists('access_checker')) {
    function access_checker($module)
    {
        return true;
        if (auth()->user()->user_type == 'super-admin') {
            return true;
        } elseif (auth()->user()->roles->count() > 0) {
            $modules = auth()->user()->roles[0]->modules;
            if (in_array($module, $modules)) {
                return true;
            } else {
                return false;
            }
        }
    }
}

if (!function_exists('exc_handler')) {
    function exc_handler($data)
    {
        try {
            $response = $data;
        } catch (Exception $exception) {
            $response = translate('not_available');
        }
        return $response;
    }
}

if (!function_exists('get_add_money_bonus')) {
    function get_add_money_bonus($amount)
    {
        $bonuses = Bonus::where('is_active', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->where('minimum_add_amount', '<=', $amount)
            ->get();

        $bonuses = $bonuses->where('minimum_add_amount', $bonuses->max('minimum_add_amount'));

        foreach ($bonuses as $key => $item) {
            $item->applied_bonus_amount = $item->bonus_amount_type == 'percent' ? ($amount * $item->bonus_amount) / 100 : $item->bonus_amount;

            if ($item->bonus_amount_type == 'percent' && $item->applied_bonus_amount > $item->maximum_bonus_amount) {
                $item->applied_bonus_amount = $item->maximum_bonus_amount;
            }
        }

        return $bonuses->max('applied_bonus_amount') ?? 0;
    }
}

if (!function_exists('build_google_route_matrix_waypoint')) {
    function build_google_route_matrix_waypoint(float $latitude, float $longitude): array
    {
        return [
            'waypoint' => [
                'location' => [
                    'latLng' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ],
                ],
            ],
        ];
    }
}

if (!function_exists('compute_google_route_matrix_distances_km')) {
    /**
     * Driving distances (km) from one origin to many destinations via Google Routes API.
     * Falls back to haversine when the API key is missing or the request fails.
     *
     * @param array<int, array{latitude: float, longitude: float}> $destinations
     * @return array<int, float|null> Distances in km, same order as $destinations
     */
    function compute_google_route_matrix_distances_km(float $originLat, float $originLng, array $destinations): array
    {
        $count = count($destinations);
        if ($count === 0) {
            return [];
        }

        $fallback = static function () use ($originLat, $originLng, $destinations): array {
            return array_map(static function (array $destination) use ($originLat, $originLng) {
                return round(get_distance(
                    [$originLat, $originLng],
                    [$destination['latitude'], $destination['longitude']]
                ), 2);
            }, $destinations);
        };

        $googleMap = business_config('google_map', 'third_party');
        $apiKey = $googleMap?->live_values['map_api_key_server'] ?? null;
        if (!$apiKey) {
            return $fallback();
        }

        $url = 'https://routes.googleapis.com/distanceMatrix/v2:computeRouteMatrix';
        $headers = [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => 'originIndex,destinationIndex,distanceMeters,status,condition',
        ];

        $results = array_fill(0, $count, null);
        $chunkSize = 25;

        foreach (array_chunk($destinations, $chunkSize, true) as $chunk) {
            $chunkKeys = array_keys($chunk);
            $destWaypoints = [];
            foreach ($chunk as $destination) {
                $destWaypoints[] = build_google_route_matrix_waypoint(
                    (float) $destination['latitude'],
                    (float) $destination['longitude']
                );
            }

            $payload = [
                'origins' => [build_google_route_matrix_waypoint($originLat, $originLng)],
                'destinations' => $destWaypoints,
                'travelMode' => 'DRIVE',
                'routingPreference' => 'TRAFFIC_AWARE',
            ];

            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                    ->timeout(15)
                    ->post($url, $payload);

                if (!$response->successful()) {
                    foreach ($chunkKeys as $globalIndex) {
                        $destination = $destinations[$globalIndex];
                        $results[$globalIndex] = round(get_distance(
                            [$originLat, $originLng],
                            [$destination['latitude'], $destination['longitude']]
                        ), 2);
                    }
                    continue;
                }

                $body = $response->json();
                if (!is_array($body)) {
                    foreach ($chunkKeys as $globalIndex) {
                        $destination = $destinations[$globalIndex];
                        $results[$globalIndex] = round(get_distance(
                            [$originLat, $originLng],
                            [$destination['latitude'], $destination['longitude']]
                        ), 2);
                    }
                    continue;
                }

                foreach ($body as $element) {
                    if (!is_array($element)) {
                        continue;
                    }
                    $destinationIndex = $element['destinationIndex'] ?? null;
                    if ($destinationIndex === null || !isset($chunkKeys[$destinationIndex])) {
                        continue;
                    }
                    $globalIndex = $chunkKeys[$destinationIndex];
                    $meters = $element['distanceMeters'] ?? null;
                    if ($meters === null) {
                        $destination = $destinations[$globalIndex];
                        $results[$globalIndex] = round(get_distance(
                            [$originLat, $originLng],
                            [$destination['latitude'], $destination['longitude']]
                        ), 2);
                        continue;
                    }
                    $results[$globalIndex] = round(((float) $meters) / 1000, 2);
                }

                foreach ($chunkKeys as $localIndex => $globalIndex) {
                    if ($results[$globalIndex] === null) {
                        $destination = $destinations[$globalIndex];
                        $results[$globalIndex] = round(get_distance(
                            [$originLat, $originLng],
                            [$destination['latitude'], $destination['longitude']]
                        ), 2);
                    }
                }
            } catch (\Throwable) {
                foreach ($chunkKeys as $globalIndex) {
                    $destination = $destinations[$globalIndex];
                    $results[$globalIndex] = round(get_distance(
                        [$originLat, $originLng],
                        [$destination['latitude'], $destination['longitude']]
                    ), 2);
                }
            }
        }

        return $results;
    }
}

if (!function_exists('get_distance')) {
    function get_distance(array $originCoordinates, array $destinationCoordinates, $unit = 'K'): float
    {
        $lat1 = (float)$originCoordinates[0];
        $lat2 = (float)$destinationCoordinates[0];
        $lon1 = (float)$originCoordinates[1];
        $lon2 = (float)$destinationCoordinates[1];

        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        } else {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);
            if ($unit == "K") {
                return ($miles * 1.609344);
            } else if ($unit == "N") {
                return ($miles * 0.8684);
            } else {
                return $miles;
            }
        }
    }
}

if (!function_exists('provider_warning_amount_calculate')) {
    function provider_warning_amount_calculate($payable, $receivable): bool|string
    {
        if ($payable > $receivable) {
            $limit_amount = (business_config('max_cash_in_hand_limit_provider', 'provider_config'))->live_values ?? 0;
            $amount = $payable - $receivable;

            $percentage_80 = 0.8 * $limit_amount;
            $percentage_100 = $limit_amount;

            $warningType = '';

            if ($amount >= $percentage_80) {
                $warningType = '80_percent';
            }

            if ($amount >= $percentage_100) {
                $warningType = '100_percent';
            }
            return $warningType;
        }
        return false;
    }
}

if (!function_exists('remove_invalid_charcaters')) {
    function remove_invalid_charcaters($str): array|string
    {
        return str_ireplace(['\'', '"', ',', ';', '<', '>', '?'], ' ', $str);
    }
}

if (!function_exists('text_variable_data_format')) {
    function text_variable_data_format($title, $booking_id, ?string $type = null, array|object|string|null $data = null, ?string $bookingType = null): array|string
    {
        $dataArray = is_object($data) ? (array) $data : (is_array($data) ? $data : []);

        $bookingStatusFromData = trim((string) ($dataArray['booking_status'] ?? ''));

        $replaceMap = [
            '{{providerName}}' => (string) ($dataArray['provider_name'] ?? ''),
            '{{scheduleTime}}' => (string) ($dataArray['schedule_time'] ?? ''),
            '{{userName}}' => (string) ($dataArray['user_name'] ?? ''),
            '{{zoneName}}' => (string) ($dataArray['zone_name'] ?? ''),
            '{{serviceManName}}' => (string) ($dataArray['service_man_name'] ?? ''),
            '{{bookingId}}' => (string) ($dataArray['booking_id'] ?? ''),
            '{{bookingStatus}}' => $bookingStatusFromData,
            '{{amount}}' => (string) ($dataArray['amount'] ?? ''),
            '{{serviceName}}' => (string) ($dataArray['service_name'] ?? ''),
            '{{otp}}' => (string) ($dataArray['otp'] ?? ''),
            '{{senderName}}' => (string) ($dataArray['sender_name'] ?? ''),
            '{{showcaseTitle}}' => (string) ($dataArray['showcase_title'] ?? $dataArray['showcaseTitle'] ?? ''),
        ];

        if ($type == 'booking' || $type == 'offline-payment') {

            if ($bookingType == 'repeat') {
                $booking = \Modules\BookingModule\Entities\BookingRepeat::find($booking_id) ?? \Modules\BookingModule\Entities\Booking::find($booking_id);
            } else {
                $booking = \Modules\BookingModule\Entities\Booking::find($booking_id);
            }

            if (!$booking) {
                return str_replace(array_keys($replaceMap), array_values($replaceMap), $title);
            }

            $fillTemplateValue = static function (string $placeholder, string $value) use (&$replaceMap): void {
                if (trim($replaceMap[$placeholder] ?? '') === '' && trim($value) !== '') {
                    $replaceMap[$placeholder] = $value;
                }
            };

            $fillTemplateValue('{{providerName}}', (string) ($booking?->provider?->company_name ?? ''));
            $fillTemplateValue('{{bookingId}}', (string) ($booking->readable_id ?? $booking->id ?? ''));
            $fillTemplateValue('{{scheduleTime}}', (string) ($booking->service_schedule ?? ''));
            if ($bookingStatusFromData === '') {
                $replaceMap['{{bookingStatus}}'] = ucfirst(str_replace('_', ' ', (string) ($booking->booking_status ?? '')));
            }
            $fillTemplateValue('{{otp}}', (string) ($booking->booking_otp ?? ''));

            if ($bookingType == 'repeat') {
                if ($booking->booking) {
                    $fillTemplateValue(
                        '{{userName}}',
                        $booking->booking->customer
                            ? trim($booking->booking->customer->first_name . ' ' . $booking->booking->customer->last_name)
                            : ''
                    );
                    $fillTemplateValue('{{zoneName}}', (string) ($booking->booking->zone?->name ?? ''));
                } else {
                    $fillTemplateValue(
                        '{{userName}}',
                        trim(($booking->customer?->first_name ?? '') . ' ' . ($booking->customer?->last_name ?? ''))
                    );
                    $fillTemplateValue('{{zoneName}}', (string) ($booking->zone?->name ?? ''));
                }
            } else {
                $fillTemplateValue(
                    '{{userName}}',
                    trim(($booking->customer?->first_name ?? '') . ' ' . ($booking->customer?->last_name ?? ''))
                );
                $fillTemplateValue('{{zoneName}}', (string) ($booking->zone?->name ?? ''));
            }

            $fillTemplateValue(
                '{{serviceManName}}',
                trim(($booking?->serviceman?->user?->first_name ?? '') . ' ' . ($booking?->serviceman?->user?->last_name ?? ''))
            );

        } elseif ($type === 'review' && filled($booking_id)) {
            $readableBookingId = notification_readable_booking_id((string) $booking_id);
            $currentBookingId = trim((string) ($replaceMap['{{bookingId}}'] ?? ''));
            if ($readableBookingId !== '' && ($currentBookingId === '' || str_contains($currentBookingId, '-'))) {
                $replaceMap['{{bookingId}}'] = $readableBookingId;
            }
        }

        return str_replace(array_keys($replaceMap), array_values($replaceMap), $title);
    }
}

if (!function_exists('config_settingss')) {
    function config_settingss($key, $settings_type)
    {
        try {
            $config = DB::table('addon_settings')->where('key_name', $key)
                ->where('settings_type', $settings_type)->first();
        } catch (Exception $exception) {
            return null;
        }

        return (isset($config)) ? $config : null;
    }
}

if (!function_exists('onErrorImage')) {
    function onErrorImage($data, $src, $error_src ,$path)
    {
        if(isset($data) && strlen($data) >1 && Storage::disk('public')->exists($path.$data)){
            return $src;
        }
        return $error_src;
    }
}

if (!function_exists('getSuperAdminId')) {
    function getSuperAdminId()
    {
        return User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;
    }
}

if (!function_exists('getServiceFee')) {
    /**
     * Total configured additional charges for the customer's current cart (ex-tax line basis).
     */
    function getServiceFee($customerUserId = null): float
    {
        $uid = $customerUserId ?? auth()->id();
        if (! $uid) {
            return 0.0;
        }

        return get_additional_charges_cart_total($uid);
    }
}

if (!function_exists('formatSubscriptionPackage')) {
    function formatSubscriptionPackage($subscriptionPackage, $features)
    {
        $featureList = [];
        foreach ($features as $feature) {
            $featureExists = $subscriptionPackage->subscriptionPackageFeature->contains(function ($value) use ($feature) {
                return $value->feature == $feature['key'];
            });
            if ($featureExists) {
                $featureList[] = $feature['value'];
            }
        }

        $bookingLimit = 'Unlimited Bookings';
        $categoryLimit = 'Unlimited Service Sub Categories';

        foreach ($subscriptionPackage->subscriptionPackageLimit as $limit) {
            if ($limit->key === 'booking' && $limit->is_limited) {
                $bookingLimit = $limit->limit_count . ' Booking Limit';
            }
            if ($limit->key === 'category' && $limit->is_limited) {
                $categoryLimit = $limit->limit_count . ' Sub Category Limit';
            }
        }

        $featureList[] = $bookingLimit;
        $featureList[] = $categoryLimit;

        $subscriptionPackage['feature_list'] = $featureList;

        unset($subscriptionPackage->subscriptionPackageFeature);
        unset($subscriptionPackage->subscriptionPackageLimit);

        return $subscriptionPackage;
    }
}

if (!function_exists('subscriptionFeatureList')) {
    function subscriptionFeatureList($subscription, $features): array
    {
        $categoryCount = 0;
        $bookingCount = 0;

        $featureList = [];
        $limitFeature = [
            'booking' => 'Unlimited',
            'category' => 'Unlimited'
        ];
        $limitLeft = [
            'booking' => 0,
            'category' => 0
        ];

        foreach ($features as $feature) {
            $featureExists = $subscription->subscriptionPackageFeature->contains(function ($value) use ($feature) {
                return $value->feature == $feature['key'];
            });
            if ($featureExists) {
                $featureList[] = $feature['key'];
            }
        }

        $featureList[] = 'booking';
        $featureList[] = 'category';

        foreach ($subscription->subscriptionPackageLimit as $limit) {
            if ($limit->key === 'booking' && $limit->is_limited) {
                $limitFeature['booking'] = $limit->limit_count;
                $limitLeft['booking'] = $limit->limit_count - $bookingCount;
            }
            if ($limit->key === 'category' && $limit->is_limited) {
                $limitFeature['category'] = $limit->limit_count;
                $limitLeft['category'] = $limit->limit_count - $categoryCount;
            }
        }

        $subscription->feature_list = $featureList;
        $subscription->feature_limit = $limitFeature;

        unset($subscription->subscriptionPackageFeature);
        unset($subscription->subscriptionPackageLimit);

        return $subscription->toArray();
    }
}



if (!function_exists('packageSubscriber')) {
    function packageSubscriber($packageSubscriber, $features)
    {
        $providerId = $packageSubscriber->provider_id;
        $packageSubscriber['total_amount'] = $packageSubscriber?->logs->where('provider_id', $providerId)->sum('package_price');
        $packageSubscriber['number_of_uses'] = $packageSubscriber?->logs->where('provider_id', $providerId)->count();
        $packageSubscriber['description'] = $packageSubscriber?->package->description;

        $featureList = [];
        foreach ($features as $feature) {
            $featureExists = $packageSubscriber->feature->contains(function ($value) use ($feature) {
                return $value->feature == $feature['key'];
            });
            if ($featureExists) {
                $featureList[] = $feature['value'];
            }
        }
        $bookingLimit = 'Unlimited Bookings';
        $categoryLimit = 'Unlimited Service Categories';

        foreach ($packageSubscriber->limits as $limit) {
            if ($limit->key === 'booking' && $limit->is_limited) {
                $bookingLimit = $limit->limit_count . ' Booking Limit';
            }
            if ($limit->key === 'category' && $limit->is_limited) {
                $categoryLimit = $limit->limit_count . ' Category Limit';
            }
        }

        $featureList[] = $bookingLimit;
        $featureList[] = $categoryLimit;

        $packageSubscriber['feature_list'] = $featureList;

        unset($packageSubscriber->feature);
        unset($packageSubscriber->limits);
        unset($packageSubscriber->logs);
        unset($packageSubscriber->package);

        return $packageSubscriber;
    }
}

if (!function_exists('apiPackageSubscriber')) {
    function apiPackageSubscriber($packageSubscriber, $features)
    {
        $categoryCount = 0;
        $bookingCount = 0;

        $startDate = $packageSubscriber?->package_start_date;
        $endDate = $packageSubscriber?->package_end_date;
        $providerId = $packageSubscriber?->provider_id;
        $providerUserId = $packageSubscriber?->provider->user_id;

        $packageSubscriber['total_amount'] = $packageSubscriber?->logs->sum('package_price');
        $packageSubscriber['number_of_uses'] = $packageSubscriber?->logs->count();
        $packageSubscriber['description'] = $packageSubscriber?->package->description;
        $packageSubscriber['is_paid'] = $packageSubscriber?->payment?->where('id', $packageSubscriber->payment_id)->value('is_paid');

        if ($startDate && $endDate) {
            $bookingCount = SubscriptionSubscriberBooking::where('provider_id', $providerId)
                ->where('package_subscriber_log_id', $packageSubscriber?->package_subscriber_log_id)
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    $startDate = Carbon::parse($startDate)->startOfDay();
                    $endDate = Carbon::parse($endDate)->endOfDay();
                    return $query->whereBetween('updated_at', [$startDate, $endDate]);
                })
                ->count();

            $categoryCount = SubscribedService::where('provider_id', $providerId)->where('is_subscribed', 1)
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    $startDate = Carbon::parse($startDate)->startOfDay();
                    $endDate = Carbon::parse($endDate)->endOfDay();
                    return $query->whereBetween('updated_at', [$startDate, $endDate]);
                })
                ->count();
        }

        $featureList = [];
        $limitFeature = [
            'booking' => 'Unlimited',
            'category' => 'Unlimited'
        ];
        $limitLeft = [
            'booking' => 0,
            'category' => 0
        ];

        foreach ($features as $feature) {
            $featureExists = $packageSubscriber->feature->contains(function ($value) use ($feature) {
                return $value->feature == $feature['key'];
            });
            if ($featureExists) {
                $featureList[] = $feature['key'];
            }
        }

        $featureList[] = 'booking';
        $featureList[] = 'category';

        foreach ($packageSubscriber->limits->where('provider_id', $providerId) as $limit) {
            if ($limit->key === 'booking' && $limit->is_limited) {
                $limitFeature['booking'] = $limit->limit_count;
                $limitLeft['booking'] = $limit->limit_count - $bookingCount;
            }
            if ($limit->key === 'category' && $limit->is_limited) {
                $limitFeature['category'] = $limit->limit_count;
                $limitLeft['category'] = $limit->limit_count - $categoryCount;
            }
        }

        $packageSubscriber['feature_list'] = $featureList;
        $packageSubscriber['feature_limit'] = $limitFeature;
        $packageSubscriber['feature_limit_left'] = $limitLeft;

        unset($packageSubscriber->feature);
        unset($packageSubscriber->limits);
        unset($packageSubscriber->logs);
        unset($packageSubscriber->package);
        unset($packageSubscriber->payment);

        return $packageSubscriber;
    }
}

if (!function_exists('saveSingleImageDataToStorage')) {
    function saveSingleImageDataToStorage($model, $modelColumn, $storageType)
    {
        \Modules\BusinessSettingsModule\Entities\Storage::updateOrCreate(
            [
                'model' => get_class($model),
                'model_id' => $model->id,
                'model_column' => $modelColumn
            ],
            [
                'storage_type' => $storageType,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
        return true;
    }
}

if (!function_exists('saveBusinessImageDataToStorage')) {
    function saveBusinessImageDataToStorage($model, $modelColumn, $storageType)
    {
        \Modules\BusinessSettingsModule\Entities\Storage::updateOrCreate(
            [
                'model' => get_class($model),
                'model_column' => $modelColumn
            ],
            [
                'model_id' => $model->id,
                'storage_type' => $storageType,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
        return true;
    }
}

if (!function_exists('public_storage_asset_url')) {
    /**
     * Build a /storage/... URL using the current request host (works on localhost and production).
     */
    function public_storage_asset_url(string $relativePath): string
    {
        $relativePath = ltrim(str_replace(['storage/', '/storage/'], '', $relativePath), '/');

        // API responses must use APP_URL so mobile clients and home-bundle sub-requests
        // never emit http://localhost/... when the public API host differs.
        if (request()?->is('api/*')) {
            $appUrl = rtrim((string) config('app.url', ''), '/');
            if ($appUrl !== '') {
                return $appUrl.'/storage/'.$relativePath;
            }
        }

        if (request()) {
            $host = request()->getSchemeAndHttpHost();
            if ($host !== '') {
                return rtrim($host, '/').'/storage/'.$relativePath;
            }
        }

        $appUrl = rtrim((string) config('app.url', ''), '/');
        if ($appUrl !== '') {
            return $appUrl.'/storage/'.$relativePath;
        }

        return asset('storage/'.$relativePath);
    }
}

if (!function_exists('normalize_identity_image_entries')) {
    /**
     * @param  mixed  $identityImages
     * @return list<array{image: string, storage: string}>
     */
    function normalize_identity_image_entries(mixed $identityImages): array
    {
        if (is_string($identityImages)) {
            $decoded = json_decode($identityImages, true);
            $identityImages = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($identityImages)) {
            return [];
        }

        $normalized = [];
        foreach ($identityImages as $item) {
            if (is_string($item) && $item !== '') {
                $normalized[] = ['image' => $item, 'storage' => 'public'];
                continue;
            }
            if (! is_array($item)) {
                continue;
            }
            $image = $item['image'] ?? $item['file'] ?? $item['path'] ?? '';
            if (is_string($image) && $image !== '') {
                $normalized[] = [
                    'image' => $image,
                    'storage' => (string) ($item['storage'] ?? $item['storage_type'] ?? 'public'),
                ];
            }
        }

        return $normalized;
    }
}

if (!function_exists('cloud_storage_public_url')) {
    /**
     * Public URL for a file on the configured S3-compatible disk (Cloudflare R2, AWS S3).
     */
    function cloud_storage_public_url(string $relativePath): string
    {
        $relativePath = ltrim(str_replace(['storage/', '/storage/'], '', $relativePath), '/');
        // DB stores logical paths without env folder; R2 objects use local/dev/prod prefix.
        $relativePath = \App\Support\StoragePathPrefix::apply(
            \App\Support\StoragePathPrefix::strip($relativePath)
        );
        $baseUrl = \App\Support\CloudStorageConfigurator::publicBaseUrl();

        if ($baseUrl !== null && $baseUrl !== '') {
            return $baseUrl.'/'.$relativePath;
        }

        return \Illuminate\Support\Facades\Storage::disk('s3')->url($relativePath);
    }
}

if (!function_exists('resolve_media_storage_url')) {
    /**
     * Resolve a public URL for a file stored on public or S3 disk.
     *
     * @param  string  $image  Filename or relative path (e.g. provider/logo/file.png)
     * @param  string  $basePath  Directory prefix when $image is only a filename
     * @param  bool  $checkExistence  When false, build the URL from the configured disk without remote exists() checks (much faster for admin lists).
     */
    function resolve_media_storage_url(
        string $image,
        string $basePath = '',
        ?string $preferredStorage = null,
        ?string $defaultPath = null,
        bool $checkExistence = true
    ): ?string {
        $image = ltrim($image, '/');
        if ($image === '') {
            return $defaultPath;
        }

        $baseKeys = str_contains($image, '/')
            ? [$image]
            : [rtrim($basePath, '/') . '/' . $image];

        if (! $checkExistence) {
            $logicalPath = $baseKeys[0];
            $disk = $preferredStorage ?? (function_exists('getDisk') ? getDisk() : 'public');

            if ($disk === 's3') {
                return cloud_storage_public_url($logicalPath);
            }

            $storageKey = \App\Support\StoragePathPrefix::apply(
                \App\Support\StoragePathPrefix::strip($logicalPath)
            );

            return public_storage_asset_url($storageKey);
        }

        $candidates = [];
        foreach ($baseKeys as $baseKey) {
            foreach (\App\Support\StoragePathPrefix::keyVariants($baseKey) as $variant) {
                $candidates[] = $variant;
            }
        }
        $candidates = array_values(array_unique($candidates));

        $disks = array_values(array_unique(array_filter([
            $preferredStorage,
            function_exists('getDisk') ? getDisk() : null,
            'public',
            's3',
        ])));

        foreach ($candidates as $candidate) {
            if (in_array('public', $disks, true)) {
                try {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($candidate)) {
                        return public_storage_asset_url($candidate);
                    }
                } catch (\Throwable $e) {
                    //
                }
            }

            foreach ($disks as $disk) {
                if ($disk === 'public') {
                    continue;
                }

                try {
                    if ($disk && \Illuminate\Support\Facades\Storage::disk($disk)->exists($candidate)) {
                        return cloud_storage_public_url($candidate);
                    }
                } catch (\Throwable $e) {
                    //
                }
            }

            if (str_starts_with($candidate, 'provider/')) {
                $disk = $preferredStorage ?? (function_exists('getDisk') ? getDisk() : 'public');
                if ($disk === 's3') {
                    return cloud_storage_public_url($candidate);
                }

                return public_storage_asset_url(
                    \App\Support\StoragePathPrefix::apply(\App\Support\StoragePathPrefix::strip($candidate))
                );
            }
        }

        $logicalPath = $candidates[0] ?? $image;
        $disk = $preferredStorage ?? (function_exists('getDisk') ? getDisk() : 'public');
        if ($disk === 's3') {
            return cloud_storage_public_url($logicalPath);
        }

        return public_storage_asset_url(
            \App\Support\StoragePathPrefix::apply(\App\Support\StoragePathPrefix::strip($logicalPath))
        );
    }
}

if (!function_exists('getSingleImageFullPath')) {
    function getSingleImageFullPath($imagePath, array|object|null $s3Storage = null, ?string $defaultPath = null, ?bool $page = null)
    {
        $preferred = ($s3Storage && ($s3Storage->storage_type ?? null) === 's3') ? 's3' : null;
        $resolved = resolve_media_storage_url(
            (string) $imagePath,
            '',
            $preferred,
            $defaultPath,
            false
        );

        if ($resolved !== null && ($defaultPath === null || $resolved !== $defaultPath)) {
            return $resolved;
        }

        if (request()->is('api/*')) {
            return $page ? $defaultPath : null;
        }

        return $defaultPath;
    }
}

if (!function_exists('getIdentityImageFullPath')) {
    function getIdentityImageFullPath($identityImages, $path, ?string $defaultPath = null)
    {
        $identityImageFullPath = [];

        foreach (normalize_identity_image_entries($identityImages) as $entry) {
            $image = $entry['image'] ?? '';
            if ($image === '') {
                continue;
            }

            $fullPath = resolve_media_storage_url(
                $image,
                $path,
                $entry['storage'] ?? null,
                null
            );

            if ($fullPath === null || $fullPath === '') {
                continue;
            }

            if (request()->is('api/*') && $defaultPath && $fullPath === $defaultPath) {
                continue;
            }

            $identityImageFullPath[] = $fullPath;
        }

        return $identityImageFullPath;
    }
}

if (!function_exists('mobile_app_icon_public_url')) {
    /**
     * Public URL for a file under storage/app/public (e.g. mobile-app/filename.webp).
     */
    function mobile_app_icon_public_url(string $imagePath): string
    {
        $imagePath = ltrim($imagePath, '/');

        if (! app()->runningInConsole() && request()) {
            return rtrim(request()->getSchemeAndHttpHost(), '/').'/storage/'.$imagePath;
        }

        return rtrim(config('app.url'), '/').'/storage/'.$imagePath;
    }
}

if (!function_exists('getBusinessSettingsImageFullPath')) {
    function getBusinessSettingsImageFullPath($key, $settingType, $path, ?string $defaultPath = null)
    {
        $image = \Modules\BusinessSettingsModule\Entities\BusinessSettings::with('storage')->where(['key_name' => $key, 'settings_type' => $settingType])->first();
        if (!$image) {
            if (request()->is('api/*')) {
                return null;
            }
            return asset($defaultPath);
        }

        $preferred = ($image->storage && $image->storage->storage_type == 's3') ? 's3' : null;
        $resolved = resolve_media_storage_url(
            $path.$image->live_values,
            '',
            $preferred,
            $defaultPath ? asset($defaultPath) : null,
            false
        );

        if ($resolved !== null) {
            return $resolved;
        }

        if (request()->is('api/*')) {
            return null;
        }

        return asset($defaultPath);
    }
}
if (!function_exists('getDataSettingsImageFullPath')) {
    function getDataSettingsImageFullPath($key, $settingType, $path, ?string $defaultPath = null)
    {
        $image = \Modules\BusinessSettingsModule\Entities\DataSetting::with('storage')->where(['key' => $key, 'type' => $settingType])->first();
        if (!$image) {
            if (request()->is('api/*')) {
                return null;
            }
            return asset($defaultPath);
        }

        $imagePath = $path . $image->value;
        $s3Storage = $image->storage;

        try {
            if ($s3Storage && $s3Storage->storage_type == 's3' && \Illuminate\Support\Facades\Storage::disk('s3')->exists($imagePath)) {
                return Storage::disk('s3')->url($imagePath);
//                $awsUrl = rtrim(config('filesystems.disks.s3.url'), '/');
//                $awsBucket = config('filesystems.disks.s3.bucket');
//                return $awsUrl . '/' . $awsBucket . '/' . $imagePath;
            }
        }catch(\Exception $exception){
            //
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($imagePath)) {
            return Storage::disk('public')->url($imagePath);
        } else {
            if (request()->is('api/*')) {
                return null;
            }
            return asset($defaultPath);
        }
    }
}

if (!function_exists('getPaymentGatewayImageFullPath')) {
    function getPaymentGatewayImageFullPath($key, $settingsType, ?string $defaultPath = null)
    {
        $addonSettings = \Modules\PaymentModule\Entities\Setting::where('key_name', $key)->where('settings_type', $settingsType)->first();
        if (!$addonSettings) {
            if (request()->is('api/*')) {
                return null;
            }
            return asset($defaultPath);
        }
        $additionalData = $addonSettings['additional_data'] != null ? json_decode($addonSettings['additional_data']) : null;

        if(!$additionalData)
        {
            return asset($defaultPath);
        }

        if ($additionalData){
            if (!$additionalData->gateway_image){
                return asset($defaultPath);
            }
        }

        $path = 'payment_modules/gateway_image/';
        $imagePath = $path . ($additionalData ? $additionalData->gateway_image : '');

        $additionalData = [
            'gateway_title' => $additionalData->gateway_title?? null,
            'gateway_image' => $additionalData->gateway_image?? null,
            'storage' => $additionalData->storage ?? 'public'
        ];

        try {
            if ($additionalData['storage'] == 's3' && \Illuminate\Support\Facades\Storage::disk('s3')->exists($imagePath)) {
                return Storage::disk('s3')->url($imagePath);
//                $awsUrl = rtrim(config('filesystems.disks.s3.url'), '/');
//                $awsBucket = config('filesystems.disks.s3.bucket');
//                return $awsUrl . '/' . $awsBucket . '/' . $imagePath;
            }
        }catch(\Exception $exception){
            //
        }

        if ($additionalData['storage'] == 'public' && \Illuminate\Support\Facades\Storage::disk('public')->exists($imagePath)) {
            return Storage::disk('public')->url($imagePath);
        }

        if (request()->is('api/*')) {
            return null;
        }

        return asset($defaultPath);
    }
}


if (!function_exists('nextBookingEligibility')) {
    function nextBookingEligibility($providerId): bool
    {
        $now = \Carbon\Carbon::now()->subDay();
        $packageSubscriber = PackageSubscriber::where('provider_id', $providerId)->first();
        $packageSubscriberLogId = $packageSubscriber?->package_subscriber_log_id;
        $providerUserId = $packageSubscriber?->provider?->user_id;
        $isPaid = $packageSubscriber?->payment?->where('id', $packageSubscriber?->payment_id)->value('is_paid');

        if ($packageSubscriber && $packageSubscriber->payment_id != null) {
            if ($isPaid){
                if ($packageSubscriber->is_canceled){
                    return false;
                }
                foreach ($packageSubscriber->limits->where('provider_id', $providerId) as $limit) {
                    if ($limit->key === 'booking') {
                        if ($limit->is_limited) {
                            $limitLeft = $limit->limit_count;

                            $startDate = $packageSubscriber->package_start_date;
                            $endDate = $packageSubscriber->package_end_date;

                            if ($startDate && $endDate) {
                                if($now > $endDate){
                                    return false;
                                }

//                                $bookingCount = SubscriptionSubscriberBooking::where('provider_id', $providerId)
//                                    ->whereBetween('updated_at', [$startDate, $endDate])
//                                    ->count();

                                $bookingCount = SubscriptionSubscriberBooking::where('provider_id', $providerId)->where('package_subscriber_log_id',$packageSubscriberLogId)
                                    ->whereBetween(DB::raw('DATE(updated_at)'), [date('Y-m-d', strtotime($startDate)), date('Y-m-d', strtotime($endDate))])
                                    ->count();

                                $leftBookingCount = $limitLeft - $bookingCount;
                                if ($leftBookingCount > 0) {
                                    return true;
                                }
                            }
                        } else {
                            return true;
                        }
                    }
                }
            }
            return false;
        }
        return true;
    }
}

if (!function_exists('minimum_booking_schedule_time')) {
    function minimum_booking_schedule_time(): \Carbon\Carbon
    {
        $value = (int) (business_config('advanced_booking_restriction_value', 'booking_setup')?->live_values ?? 2);
        $type = business_config('advanced_booking_restriction_type', 'booking_setup')?->live_values;
        $restrictionEnabled = (int) (business_config('schedule_booking_time_restriction', 'booking_setup')?->live_values ?? 0);

        if ($restrictionEnabled !== 1) {
            return \Carbon\Carbon::now();
        }

        if ($type === 'day' && $value > 0) {
            return \Carbon\Carbon::now()->addDays($value);
        }

        if ($type === 'hour' && $value > 0) {
            return \Carbon\Carbon::now()->addHours($value);
        }

        if ($type === 'minute' && $value > 0) {
            return \Carbon\Carbon::now()->addMinutes($value);
        }

        return \Carbon\Carbon::now()->addHours(2);
    }
}

if (!function_exists('company_service_hours_config')) {
    function company_service_hours_config(): ?array
    {
        $enabled = (int) (business_config('company_service_hours_enabled', 'booking_setup')?->live_values ?? 0);
        if ($enabled !== 1) {
            return null;
        }

        $startTime = business_config('company_service_start_time', 'booking_setup')?->live_values;
        $endTime = business_config('company_service_end_time', 'booking_setup')?->live_values;
        if (empty($startTime) || empty($endTime)) {
            return null;
        }

        $weekendsRaw = business_config('company_service_weekends', 'booking_setup')?->live_values;
        $weekends = $weekendsRaw ? json_decode($weekendsRaw, true) : [];
        $weekends = is_array($weekends) ? array_map('strtolower', $weekends) : [];

        return [
            'enabled' => true,
            'start_time' => (string) $startTime,
            'end_time' => (string) $endTime,
            'weekends' => $weekends,
        ];
    }
}

if (!function_exists('is_within_company_service_hours')) {
    function is_within_company_service_hours(\Carbon\Carbon $dateTime): bool
    {
        $config = company_service_hours_config();
        if ($config === null) {
            return true;
        }

        $day = strtolower($dateTime->format('l'));
        if (in_array($day, $config['weekends'], true)) {
            return false;
        }

        $time = $dateTime->format('H:i:s');
        $start = strlen($config['start_time']) === 5
            ? $config['start_time'] . ':00'
            : $config['start_time'];
        $end = strlen($config['end_time']) === 5
            ? $config['end_time'] . ':00'
            : $config['end_time'];

        return $time >= $start && $time <= $end;
    }
}

if (!function_exists('resolve_company_service_schedule')) {
    function resolve_company_service_schedule(\Carbon\Carbon $requested): \Carbon\Carbon
    {
        $config = company_service_hours_config();
        if ($config === null) {
            return $requested;
        }

        $minimum = minimum_booking_schedule_time();

        if ($requested->gte($minimum) && is_within_company_service_hours($requested)) {
            return $requested;
        }

        $anchor = $requested->lt($minimum) ? $minimum->copy() : $requested->copy();

        for ($dayOffset = 0; $dayOffset < 14; $dayOffset++) {
            $day = $anchor->copy()->startOfDay()->addDays($dayOffset);
            $dayName = strtolower($day->format('l'));
            if (in_array($dayName, $config['weekends'], true)) {
                continue;
            }

            $start = \Carbon\Carbon::parse($day->format('Y-m-d') . ' ' . $config['start_time']);
            $end = \Carbon\Carbon::parse($day->format('Y-m-d') . ' ' . $config['end_time']);
            $slot = $start->copy();

            if ($dayOffset === 0) {
                if ($anchor->lt($slot)) {
                    $candidate = $slot->lt($minimum) ? $minimum->copy() : $slot->copy();
                    if ($candidate->gte($minimum) && is_within_company_service_hours($candidate)) {
                        return $candidate;
                    }
                    continue;
                }
                if ($anchor->lte($end) && is_within_company_service_hours($anchor)) {
                    return $anchor->lt($minimum) ? $minimum->copy() : $anchor->copy();
                }
                continue;
            }

            $candidate = $slot->lt($minimum) ? $minimum->copy() : $slot->copy();
            if ($candidate->gte($minimum) && is_within_company_service_hours($candidate)) {
                return $candidate;
            }
        }

        return $anchor->lt($minimum) ? $minimum->copy() : $anchor->copy();
    }
}

if (!function_exists('normalize_company_service_schedule')) {
    function normalize_company_service_schedule(string $schedule): string
    {
        $dateTime = \Carbon\Carbon::parse($schedule);

        return resolve_company_service_schedule($dateTime)->format('Y-m-d H:i:s');
    }
}

if (!function_exists('scheduleBookingEligibility')) {
    function scheduleBookingEligibility($providerId): bool
    {
        $now = \Carbon\Carbon::now();
        $packageSubscriber = PackageSubscriber::where('provider_id', $providerId)->first();

        if ($packageSubscriber) {
            if ($packageSubscriber->payment_id) {

                if ($packageSubscriber->is_canceled){
                    return false;
                }

                $startDate = $packageSubscriber->package_start_date;
                $endDate = $packageSubscriber->package_end_date;

                if ($startDate && $endDate) {
                    if ($now > $endDate) {
                        return false;
                    }

                    $featureExists = $packageSubscriber->feature->contains(function ($value) {
                        return $value->feature === 'schedule_service';
                    });

                    if ($featureExists) {
                        return true;
                    }
                }
            }
            return false;
        }

        return true;
    }
}

if (!function_exists('chatEligibility')) {
    function chatEligibility($providerId): bool
    {
        $now = \Carbon\Carbon::now();
        $packageSubscriber = PackageSubscriber::where('provider_id', $providerId)->first();

        if ($packageSubscriber) {
            if ($packageSubscriber->payment_id) {

                if ($packageSubscriber->is_canceled){
                    return false;
                }

                $startDate = $packageSubscriber->package_start_date;
                $endDate = $packageSubscriber->package_end_date;

                if ($startDate && $endDate) {
                    if ($now > $endDate) {
                        return false;
                    }

                    $featureExists = $packageSubscriber->feature->contains(function ($value) {
                        return $value->feature === 'chat';
                    });

                    if ($featureExists) {
                        return true;
                    }
                }
            }
            return false;
        }

        return true;
    }
}

if (!function_exists('advertisementsEligibility')) {
    function advertisementsEligibility($providerId): bool
    {
        $now = \Carbon\Carbon::now();
        $packageSubscriber = PackageSubscriber::where('provider_id', $providerId)->first();

        if ($packageSubscriber) {
            if ($packageSubscriber->payment_id) {

                if ($packageSubscriber->is_canceled){
                    return false;
                }

                $startDate = $packageSubscriber->package_start_date;
                $endDate = $packageSubscriber->package_end_date;

                if ($startDate && $endDate) {
                    if ($now > $endDate) {
                        return false;
                    }

                    $featureExists = $packageSubscriber->feature->contains(function ($value) {
                        return $value->feature === 'advertisement';
                    });

                    if ($featureExists) {
                        return true;
                    }
                }
            }
            return false;
        }

        return true;
    }
}

if (!function_exists('mobileAppCheck')) {
    function mobileAppCheck($user, $module): bool
    {
        if ($user) {
            $provider = Provider::where('user_id', $user->id)->first();
            if ($provider) {

                $providerId = $provider->id;
                $packageSubscriber = PackageSubscriber::where('provider_id', $providerId)->with('feature')->first();
                if ($packageSubscriber) {
                    $featureKeys = $packageSubscriber->feature->pluck('feature')->toArray();
                    if (in_array($module, $featureKeys) ) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }
        return true;
    }
}

if (!function_exists('sendDeviceNotificationPermission')) {
    function sendDeviceNotificationPermission($providerId): bool
    {
        $providerSubscription = PackageSubscriber::where('provider_id', $providerId)->first();
        $endDate = optional($providerSubscription)->package_end_date;
        $canceled = optional($providerSubscription)->is_canceled;
        $packageEndDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
        $currentDate = Carbon::now()->subDay();
        $isPackageEnded = $packageEndDate ? $currentDate->diffInDays($packageEndDate, false) : null;
        $scheduleBookingEligibility = nextBookingEligibility($providerId);

        if ($providerSubscription) {
            if ($isPackageEnded > 0 && !$canceled && $scheduleBookingEligibility) {
                return true;
            }else{
                return false;
            }
        }

        return true;
    }
}

if (! function_exists('resolve_provider_org_id_for_user')) {
    function resolve_provider_org_id_for_user(?\Modules\UserManagement\Entities\User $user): ?string
    {
        if (! $user) {
            return null;
        }

        return match ($user->user_type) {
            PROVIDER_USER_TYPES[0] => $user->provider?->id,
            PROVIDER_USER_TYPES[1] => filled($user->provider_id ?? null)
                ? (string) $user->provider_id
                : $user->provider?->id,
            PROVIDER_USER_TYPES[2] => $user->serviceman?->provider_id,
            default => null,
        };
    }
}

if (! function_exists('is_provider_org_chat_user')) {
    function is_provider_org_chat_user(?\Modules\UserManagement\Entities\User $user): bool
    {
        return $user && in_array($user->user_type, [PROVIDER_USER_TYPES[0], PROVIDER_USER_TYPES[1]], true);
    }
}

if (!function_exists('isNotificationActive')) {
   function isNotificationActive(?string $providerId, string $key, string $type, string $userType): ?bool
   {
        $notificationSetup = NotificationSetup::where('key', $key)->where('user_type', $userType)->get();

        foreach ($notificationSetup as $setup) {
            $adminSettings = json_decode($setup->value);
            $providerSettings = null;

            if ($providerId) {
                $providerSettings = $setup->providerNotifications()->where('provider_id', $providerId)->first();
                $providerSettings = $providerSettings ? json_decode($providerSettings->value) : null;
            }

            $settingValue = $providerSettings->$type ?? $adminSettings->$type;

            if (is_null($settingValue)) {
                return false;
            }

            return (bool) $settingValue;
        }

        return false;
    }
}

if (!function_exists('group_notification_messages_by_category')) {
    function group_notification_messages_by_category(array $notifications): array
    {
        $grouped = [];
        foreach ($notifications as $notification) {
            $category = $notification['category'] ?? 'other';
            $grouped[$category][] = $notification;
        }

        $ordered = [];
        foreach (NOTIFICATION_MESSAGE_CATEGORIES as $category) {
            if (!empty($grouped[$category])) {
                $ordered[$category] = $grouped[$category];
            }
        }

        if (!empty($grouped['other'])) {
            $ordered['other'] = $grouped['other'];
        }

        return $ordered;
    }
}

if (!function_exists('checkCurrency')) {
   function checkCurrency($data, ?string $type = null)
   {
       $digitalPayment = business_config('digital_payment', 'service_setup')->live_values;
       $publishedStatus = 0;

       try {
           $full_data = include('Modules/Gateways/Addon/info.php');
           $publishedStatus = $full_data['is_published'] == 1 ? 1 : 0;
       } catch (\Exception $exception) {
       }

       if($digitalPayment){
           if($type === null) {
               if ($publishedStatus == 1) {
                   $methods = DB::table('addon_settings')->where('is_active', 1)->where('settings_type', 'payment_config')->get();
                   $env = env('APP_ENV') == 'live' ? 'live' : 'test';
                   $credentials = $env . '_values';

               } else {
                   $methods = DB::table('addon_settings')->where('is_active', 1)->whereIn('settings_type', ['payment_config'])->whereIn('key_name', ['ssl_commerz', 'paypal', 'stripe', 'razor_pay', 'senang_pay', 'paytabs', 'paystack', 'paymob_accept', 'paytm', 'flutterwave', 'liqpay', 'bkash', 'mercadopago'])->get();
                   $env = env('APP_ENV') == 'live' ? 'live' : 'test';
                   $credentials = $env . '_values';

               }

               $getData = [];
               foreach ($methods as $method) {
                   $credentialsData = json_decode($method->$credentials);
                   $additional_data = json_decode($method->additional_data);
                   if ($credentialsData?->status == 1) {
                       $getData[] = [
                           'gateway' => $method->key_name,
                           'gateway_title' => $additional_data?->gateway_title,
                           'gateway_image' => $additional_data?->gateway_image
                       ];
                   }
               }

               if (is_array($getData)) {
                   foreach ($getData as $payment_gateway) {
                       $supportedCurrencies = getPaymentGatewaySupportedCurrencies($payment_gateway['gateway']);
                       if (!empty($supportedCurrencies) && !array_key_exists($data, $supportedCurrencies)) {
                           return $payment_gateway['gateway'];
                       }
                   }
               }
           }
           elseif($type == 'payment_gateway'){
               $currency = business_config('currency_code', 'business_information')->live_values;
               if(!empty(getPaymentGatewaySupportedCurrencies($data)) && array_key_exists($currency, getPaymentGatewaySupportedCurrencies($data))){
                   return  $data;
               }
           }
       }

       return false;
    }
}

if (!function_exists('getPaymentGatewaySupportedCurrencies')) {
   function getPaymentGatewaySupportedCurrencies(?string $key = null): array
   {
       $paymentGateway = [
           "amazon_pay" => [
               "USD" => "United States Dollar",
               "GBP" => "Pound Sterling",
               "EUR" => "Euro",
               "JPY" => "Japanese Yen",
               "AUD" => "Australian Dollar",
               "NZD" => "New Zealand Dollar",
               "CAD" => "Canadian Dollar"
           ],
           "bkash" => [
               "BDT" => "Bangladeshi Taka"
           ],
           "cashfree" => [
               "INR" => "Indian Rupee"
           ],
           "ccavenue" => [
               "INR" => "Indian Rupee"
           ],
           "ccavenue" => [
               "INR" => "Indian Rupee"
           ],
           "esewa" => [
               "NPR" => "Nepalese Rupee"
           ],
           "fatoorah" => [
               "KWD" => "Kuwaiti Dinar",
               "SAR" => "Saudi Riyal"
           ],
           "flutterwave" => [
               "NGN" => "Nigerian Naira",
               "GHS" => "Ghanaian Cedi",
               "KES" => "Kenyan Shilling",
               "ZAR" => "South African Rand",
               "USD" => "United States Dollar",
               "EUR" => "Euro",
               "GBP" => "Pound Sterling",
               "XAF" => "Central African CFA Franc"
           ],
           "foloosi" => [
               "AED" => "United Arab Emirates Dirham"
           ],
           "hubtel" => [
               "GHS" => "Ghanaian Cedi"
           ],
           "hyper_pay" => [
               "AED" => "United Arab Emirates Dirham",
               "SAR" => "Saudi Riyal",
               "EGP" => "Egyptian Pound",
               "BHD" => "Bahraini Dinar",
               "KWD" => "Kuwaiti Dinar",
               "OMR" => "Omani Rial",
               "QAR" => "Qatari Riyal",
               "USD" => "United States Dollar"
           ],
           "instamojo" => [
               "INR" => "Indian Rupee"
           ],
           "iyzi_pay" => [
               "TRY" => "Turkish Lira"
           ],
           "liqpay" => [
               "UAH" => "Ukrainian Hryvnia",
               "USD" => "United States Dollar",
               "EUR" => "Euro"
           ],
           "maxicash" => [
               "PHP" => "Philippine Peso"
           ],
           "mercadopago" => [
               "ARS" => "Argentine Peso",
               "BRL" => "Brazilian Real",
               "CLP" => "Chilean Peso",
               "COP" => "Colombian Peso",
               "MXN" => "Mexican Peso",
               "PEN" => "Peruvian Sol",
               "UYU" => "Uruguayan Peso",
               "USD" => "United States Dollar"
           ],
           "momo" => [
               "VND" => "Vietnamese Dong"
           ],
           "moncash" => [
               "HTG" => "Haitian Gourde"
           ],
           "payfast" => [
               "ZAR" => "South African Rand"
           ],
           "paymob_accept" => [
               "EGP" => "Egyptian Pound"
           ],
           "paypal" => [
               "AUD" => "Australian Dollar",
               "BRL" => "Brazilian Real",
               "CAD" => "Canadian Dollar",
               "CZK" => "Czech Koruna",
               "DKK" => "Danish Krone",
               "EUR" => "Euro",
               "HKD" => "Hong Kong Dollar",
               "HUF" => "Hungarian Forint",
               "INR" => "Indian Rupee",
               "ILS" => "Israeli New Shekel",
               "JPY" => "Japanese Yen",
               "MYR" => "Malaysian Ringgit",
               "MXN" => "Mexican Peso",
               "TWD" => "New Taiwan Dollar",
               "NZD" => "New Zealand Dollar",
               "NOK" => "Norwegian Krone",
               "PHP" => "Philippine Peso",
               "PLN" => "Polish Zloty",
               "GBP" => "Pound Sterling",
               "RUB" => "Russian Ruble",
               "SGD" => "Singapore Dollar",
               "SEK" => "Swedish Krona",
               "CHF" => "Swiss Franc",
               "THB" => "Thai Baht",
               "TRY" => "Turkish Lira",
               "USD" => "United States Dollar"
           ],
           "paystack" => [
               "NGN" => "Nigerian Naira",
               "KES" => "Kenyan Shilling"
           ],
           "paytabs" => [
               "AED" => "United Arab Emirates Dirham",
               "SAR" => "Saudi Riyal",
               "BHD" => "Bahraini Dinar",
               "KWD" => "Kuwaiti Dinar",
               "OMR" => "Omani Rial",
               "QAR" => "Qatari Riyal",
               "EGP" => "Egyptian Pound",
               "USD" => "United States Dollar"
           ],
           "paytm" => [
               "INR" => "Indian Rupee"
           ],
           "phonepe" => [
               "INR" => "Indian Rupee"
           ],
           "pvit" => [
               "NGN" => "Nigerian Naira"
           ],
           "razor_pay" => [
               "INR" => "Indian Rupee"
           ],
           "senang_pay" => [
               "MYR" => "Malaysian Ringgit"
           ],
           "sixcash" => [
               "BDT" => "Bangladeshi Taka"
           ],
           "ssl_commerz" => [
               "BDT" => "Bangladeshi Taka"
           ],
           "stripe" => [
               "USD" => "United States Dollar",
               "AUD" => "Australian Dollar",
               "CAD" => "Canadian Dollar",
               "EUR" => "Euro",
               "GBP" => "Pound Sterling",
               "JPY" => "Japanese Yen",
               "NZD" => "New Zealand Dollar",
               "CHF" => "Swiss Franc",
               "DKK" => "Danish Krone",
               "NOK" => "Norwegian Krone",
               "SEK" => "Swedish Krona",
               "SGD" => "Singapore Dollar",
               "HKD" => "Hong Kong Dollar",
               "MXN" => "Mexican Peso",
           ],
           "swish" => [
               "SEK" => "Swedish Krona"
           ],
           "tap" => [
               "AED" => "United Arab Emirates Dirham",
               "SAR" => "Saudi Riyal",
               "BHD" => "Bahraini Dinar",
               "KWD" => "Kuwaiti Dinar",
               "OMR" => "Omani Rial",
               "QAR" => "Qatari Riyal"
           ],
           "thawani" => [
               "OMR" => "Omani Rial"
           ],
           "viva_wallet" => [
               "EUR" => "Euro"
           ],
           "worldpay" => [
               "GBP" => "Pound Sterling",
               "USD" => "United States Dollar",
               "EUR" => "Euro",
               "JPY" => "Japanese Yen"
           ],
           "xendit" => [
               "IDR" => "Indonesian Rupiah",
               "PHP" => "Philippine Peso",
               "VND" => "Vietnamese Dong",
               "THB" => "Thai Baht",
               "MYR" => "Malaysian Ringgit",
               "SGD" => "Singapore Dollar"
           ],
       ];

       if ($key) {
           return array_key_exists($key,$paymentGateway) ?  $paymentGateway[$key] : [];
       }
       return $paymentGateway;
    }
}

if (!function_exists('getProviderSettings')) {
    function getProviderSettings($providerId, $key, $type)
    {
        $setting = \Modules\ProviderManagement\Entities\ProviderSetting::where([
            'key_name'      => $key,
            'provider_id'   => $providerId,
            'settings_type' => $type,
        ])->first();

        if ($setting) {
            $decoded = json_decode($setting->live_values, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return [];

    }
}

if (!function_exists('hidden_mobile_business_page_keys')) {
    /** Page keys excluded from customer/provider app menus. */
    function hidden_mobile_business_page_keys(): array
    {
        return [
            'cancellation-policy',
            'refund-policy',
            'cancellation_policy',
            'refund_policy',
        ];
    }
}

if (!function_exists('mobile_visible_business_pages')) {
    /**
     * Active business pages exposed to mobile apps (cancellation/refund policies hidden).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    function mobile_visible_business_pages()
    {
        return \Modules\BusinessSettingsModule\Entities\BusinessPageSetting::query()
            ->where('is_active', 1)
            ->whereNotIn('page_key', hidden_mobile_business_page_keys())
            ->orderByDesc('is_default')
            ->orderBy('created_at', 'ASC')
            ->get()
            ->map(static function ($page) {
                return [
                    'page_key' => $page->page_key,
                    'title' => $page->title,
                    'is_default' => $page->is_default,
                    'image_full_path' => $page->image_full_path,
                ];
            });
    }
}

if (!function_exists('provider_can_receive_bookings')) {
    /**
     * Only admin-approved, active providers may see or accept open booking requests.
     */
    function provider_can_receive_bookings(?\Modules\ProviderManagement\Entities\Provider $provider): bool
    {
        if (! $provider) {
            return false;
        }

        return (string) ($provider->is_approved ?? '') === '1'
            && (int) ($provider->is_active ?? 0) === 1;
    }
}

if (!function_exists('provider_accepts_booking_service_location')) {
    /**
     * Whether a provider's configured service locations include the booking's (customer vs provider site).
     * If the provider has no service_location setting (empty / missing), they are not excluded — avoids hiding
     * everyone when settings were never saved (decoded JSON is []).
     */
    function provider_accepts_booking_service_location(string $providerId, ?string $bookingServiceLocation): bool
    {
        if ($bookingServiceLocation === null || $bookingServiceLocation === '') {
            return true;
        }

        $configured = getProviderSettings(providerId: $providerId, key: 'service_location', type: 'provider_config');
        if (! is_array($configured) || $configured === []) {
            return true;
        }

        $normalizedBooking = (string) $bookingServiceLocation;
        foreach (array_values($configured) as $value) {
            if ((string) $value === $normalizedBooking) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('checkActiveSMSGatewayCount')) {
    function checkActiveSMSGatewayCount()
    {
        $dataValues = Setting::where('settings_type', 'sms_config')->get();
        $count = 0;
        foreach ($dataValues as $gateway) {
            $status = $gateway?->live_values['status'] ?? 0;
            if ($status == 1) {
                $count = 1;
            }
        }

        $firebaseOtpConfig = business_config('firebase_otp_verification', 'third_party');
        $firebaseOtpStatus = (int)$firebaseOtpConfig?->live_values['status'] ?? null;

        if ($firebaseOtpStatus == 1) {
            $count = 1;
        }

         return (((login_setup('phone_verification'))->value ?? 0 ) == 1 && $count == 1 ? 1 : 0);

    }
}

if (!function_exists('readableUploadMaxFileSize')) {
    function readableUploadMaxFileSize($fileType)
    {
        $uploadMaxFileSize = uploadMaxFileSize($fileType);

        return convertToReadableSize($uploadMaxFileSize);

    }
}

if (!function_exists('uploadMaxFileSizeInKB')) {
    function uploadMaxFileSizeInKB($fileType = 'image')
    {
        $uploadMaxFileSize = uploadMaxFileSize($fileType);
        $uploadMaxFileSize = $uploadMaxFileSize / 1024;

        return $uploadMaxFileSize;

    }
}

if (!function_exists('convertToReadableSize')) {
    function convertToReadableSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824) . 'GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576) . 'MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024) . 'KB';
        } else {
            return $bytes . 'B';
        }
    }
}

if (!function_exists('uploadMaxFileSize')) {
    function uploadMaxFileSize($fileType) {

        $phpLimit = convertToBytes(ini_get('upload_max_filesize'));

        if (env('APP_ENV') === 'demo') {
            $appLimit = convertToBytes( '1M');
        }else{
            $appLimit = convertToBytes($fileType === 'image' ? '20M' : '50M');
        }

        return min($phpLimit, $appLimit);
    }
}

if (!function_exists('convertToBytes')) {
    function convertToBytes($value)
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $num = (int) $value;

        switch ($last) {
            case 'g':
                $num *= 1024;
            case 'm':
                $num *= 1024;
            case 'k':
                $num *= 1024;
        }

        return $num;
    }
}


if (!function_exists('getSetupGuideSteps')) {
    function getSetupGuideSteps($panel = 'admin_panel', $user = null, $platform = 'web'): array
    {
        $steps = [];

        if ($panel === 'admin_panel') {
            $steps = [
                'business_information' => [
                    'key'   => 'business_information',
                    'title' => 'setup_business_info',
                    'route' => route('admin.business-settings.get-business-information', ['web_page' => 'business_setup']),
                    'order' => 1,
                ],
                'business_plan' => [
                    'key'   => 'business_plan',
                    'title' => 'setup_business_plan',
                    'route' => route('admin.business-settings.get-business-information', ['web_page' => 'business_plan']),
                    'order' => 2,
                ],
                'google_map_configuration' => [
                    'key'   => 'google_map_configuration',
                    'title' => 'setup_google_map_configuration',
                    'route' => route('admin.configuration.third-party', 'map-api'),
                    'order' => 3,
                ],
                'email_configuration' => [
                    'key'   => 'email_configuration',
                    'title' => 'setup_email_configuration',
                    'route' => route('admin.configuration.third-party', 'email-config'),
                    'order' => 4,
                ],
                'notification_configuration' => [
                    'key'   => 'notification_configuration',
                    'title' => 'setup_notification_configuration',
                    'route' => route('admin.configuration.third-party', 'firebase-configuration'),
                    'order' => 5,
                ],
                'login_option' => [
                    'key'   => 'login_option',
                    'title' => 'explore_login_option',
                    'route' => route('admin.business-settings.login.setup'),
                    'order' => 6,
                ],
                'digital_payment' => [
                    'key'   => 'digital_payment',
                    'title' => 'explore_digital_payment',
                    'route' => route('admin.configuration.third-party', ['webPage' => 'payment_config', 'type' => 'digital_payment']),
                    'order' => 7,
                ],
            ];

            // apply checked status
            $options = $user ? getSetupGuidelineTutorialOptions(user: $user, platform:  $platform, userType:  'admin') : [];
            foreach ($steps as $key => &$step) {
                $step['checked'] = !empty($options[$key]) && (int)$options[$key] === 1;
            }

            // calculate completion percentage
            $totalSteps = count($steps);
            $completedSteps = collect($steps)->where('checked', true)->count();
            $percentage = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;
            $rotation = $percentage * 3.6; // 0–360

            $isGuidelineDataExist = SettingsTutorials::where([
                'user_id'  => auth()->id(),
                'platform' => 'web',
            ])->first();

            $isFirstTimeGuide = is_null($isGuidelineDataExist);

            return [
                'steps' => $steps,
                'percentage' => $percentage,
                'rotation' => $rotation,
                'isFirstTimeGuide' => $isFirstTimeGuide,
            ];
        }

        if ($panel === 'provider_panel') {
            $steps = [
                'business_information' => [
                    'key'   => 'business_information',
                    'title' => 'setup_business_info',
                    'route' => route('provider.business-settings.get-business-information', ['web_page' => 'businessinfos']),
                    'order' => 1,
                ],
                'business_plan' => [
                    'key'   => 'business_plan',
                    'title' => 'explore_business_plan',
                    'route' => route('provider.subscription-package.details'),
                    'order' => 2,
                ],
                'subscribe_services' => [
                    'key'   => 'subscribe_services',
                    'title' => 'subscribe_a_services',
                    'route' => route('provider.service.available'),
                    'order' => 3,
                ],
                'payment_information' => [
                    'key'   => 'payment_information',
                    'title' => 'payment_information',
                    'route' => route('provider.settings.payment-information.index'),
                    'order' => 4,
                ],
                'service_availability' => [
                    'key'   => 'service_availability',
                    'title' => 'setup_service_availability',
                    'route' => route('provider.business-settings.get-business-information', ['web_page' => 'service_availability']),
                    'order' => 5,
                ],
            ];

            // apply checked status
            $options = $user ? getSetupGuidelineTutorialOptions(user: $user, platform: $platform, userType: 'provider' ) : [];
            foreach ($steps as $key => &$step) {
                $step['checked'] = !empty($options[$key]) && (int)$options[$key] === 1;
            }

            // calculate completion percentage
            $totalSteps = count($steps);
            $completedSteps = collect($steps)->where('checked', true)->count();
            $percentage = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;
            $rotation = $percentage * 3.6; // 0–360

            $isGuidelineDataExist = SettingsTutorials::where([
                'user_id'  => auth()->id(),
                'platform' => 'web',
            ])->first();

            $isFirstTimeGuide = is_null($isGuidelineDataExist);

            return [
                'steps' => $steps,
                'percentage' => $percentage,
                'rotation' => $rotation,
                'isFirstTimeGuide' => $isFirstTimeGuide,
            ];
        }

        return [
            'steps' => $steps,
            'percentage' => 0,
            'rotation' => 0,
            'isFirstTimeGuide' => false,
        ];
    }
}

if (!function_exists('getSetupGuidelineTutorialOptions')) {
    function getSetupGuidelineTutorialOptions($user, string $platform = 'web', $userType = 'admin'): array
    {
        if ($userType == 'admin'){
            $defaults = [
                'business_information'       => 0,
                'business_plan'              => 0,
                'google_map_configuration'   => 0,
                'email_configuration'        => 0,
                'notification_configuration' => 0,
                'login_option'               => 0,
                'digital_payment'            => 0,
            ];
        }else{
            $defaults = [
                'business_information' => 0,
                'business_plan'        => 0,
                'subscribe_services'   => 0,
                'payment_information'  => 0,
                'service_availability' => 0,
            ];
        }


        if (!$user) {
            return $defaults;
        }

        $tutorial = $user->getTutorialByPlatform($platform);

        if ($tutorial && is_array($tutorial->options)) {
            return array_replace($defaults, $tutorial->options);
        }

        return $defaults;
    }
}


if (!function_exists('updateSetupGuidelineTutorialsOptions')) {
    function updateSetupGuidelineTutorialsOptions($userId, $option, $platform = 'web'): void
    {
        $tutorial = SettingsTutorials::firstOrNew([
            'user_id'  => $userId,
            'platform' => $platform,
        ]);

        $options = is_array($tutorial->options) ? $tutorial->options : [];

        if (isset($options[$option]) && $options[$option] == 1) {
            return;
        }

        $options[$option] = 1;

        $tutorial->options = $options;
        $tutorial->save();
    }
}

if (!function_exists('adminSetupGuideWelcomeAcknowledged')) {
    function adminSetupGuideWelcomeAcknowledged(int|string|null $userId): bool
    {
        if (!$userId) {
            return false;
        }

        $row = SettingsTutorials::where([
            'user_id' => $userId,
            'platform' => 'web',
        ])->first();

        if (!$row || !is_array($row->options)) {
            return false;
        }

        return !empty($row->options['admin_setup_welcome_seen']);
    }
}

if (!function_exists('acknowledgeAdminSetupGuideWelcome')) {
    function acknowledgeAdminSetupGuideWelcome(int|string|null $userId): void
    {
        if (!$userId) {
            return;
        }

        $tutorial = SettingsTutorials::firstOrNew([
            'user_id' => $userId,
            'platform' => 'web',
        ]);

        $options = is_array($tutorial->options) ? $tutorial->options : [];
        $options['admin_setup_welcome_seen'] = 1;
        $tutorial->options = $options;
        $tutorial->save();
    }
}

if (!function_exists('setupGuidelineRouteModify')) {
    function setupGuidelineRouteModify(string $url): string
    {
        $parsed = parse_url($url);

        // Existing query params
        $query = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }

        // Add / override from_guide
        $query['from_guide'] = 1;

        // Build base URL
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = $parsed['path'] ?? '';

        return $scheme
            . $host
            . $port
            . $path
            . '?' . http_build_query($query);
    }
}

if (!function_exists('provider_default_password_plain')) {
    /**
     * Plain-text password for new provider-admin users; must match the value used as login password with phone.
     * Uses contact person phone (same as users.phone) so web/API login stays consistent.
     */
    function provider_default_password_plain(?string $contactPersonPhone): string
    {
        return (string) ($contactPersonPhone ?? '');
    }
}

if (!function_exists('user_can_use_customer_app')) {
    /**
     * Whether this account may use the customer app (book as customer) and customer API with a customer token.
     */
    function user_can_use_customer_app(?User $user): bool
    {
        if ($user === null) {
            return false;
        }
        if (in_array($user->user_type, CUSTOMER_USER_TYPES, true)) {
            return true;
        }

        return $user->user_type === 'provider-admin' && (bool) $user->customer_app_access;
    }
}

if (!function_exists('grant_customer_app_access_for_provider')) {
    /**
     * Allow a provider-admin to use the customer app with the same phone (dual role).
     */
    function grant_customer_app_access_for_provider(User $user): User
    {
        if ($user->user_type === 'provider-admin' && ! $user->customer_app_access) {
            $user->customer_app_access = true;
            $user->save();
        }

        return $user->fresh() ?? $user;
    }
}

if (!function_exists('company_default_tax_percentage')) {
    function company_default_tax_percentage(): float
    {
        $row = business_config('default_tax_percentage', 'business_information');
        if ($row === null || $row->live_values === null || $row->live_values === '') {
            return 0.0;
        }

        return (float) $row->live_values;
    }
}

if (!function_exists('company_default_tax_label')) {
    function company_default_tax_label(): string
    {
        $row = business_config('default_tax_label', 'business_information');
        if ($row === null || $row->live_values === null || $row->live_values === '') {
            return translate('tax');
        }

        return (string) $row->live_values;
    }
}

if (!function_exists('booking_tax_excluded_bracket_hint')) {
    /**
     * Hint for subtotals excluding tax, e.g. "(GST excluded)" using the configured default tax label.
     */
    function booking_tax_excluded_bracket_hint(): string
    {
        return '(' . company_default_tax_label() . ' ' . translate('excluded') . ')';
    }
}

if (!function_exists('effective_service_tax_percentage')) {
    /**
     * Resolved tax % for a service: service override → subcategory → category → company default.
     */
    function effective_service_tax_percentage($service): float
    {
        if (!$service instanceof \Modules\ServiceManagement\Entities\Service) {
            return company_default_tax_percentage();
        }

        if ($service->getAttribute('tax') !== null && $service->getAttribute('tax') !== '') {
            return (float) $service->tax;
        }

        $sub = $service->relationLoaded('subCategory')
            ? $service->getRelation('subCategory')
            : $service->subCategory()->first();
        if ($sub && $sub->tax_percentage !== null && $sub->tax_percentage !== '') {
            return (float) $sub->tax_percentage;
        }

        $cat = $service->relationLoaded('category')
            ? $service->getRelation('category')
            : $service->category()->first();
        if ($cat && $cat->tax_percentage !== null && $cat->tax_percentage !== '') {
            return (float) $cat->tax_percentage;
        }

        return company_default_tax_percentage();
    }
}

if (!function_exists('effective_service_tax_label')) {
    function effective_service_tax_label($service): string
    {
        if (!$service instanceof \Modules\ServiceManagement\Entities\Service) {
            return company_default_tax_label();
        }

        if ($service->getAttribute('tax') !== null && $service->getAttribute('tax') !== '') {
            $lbl = $service->tax_label;

            return ($lbl !== null && $lbl !== '') ? (string) $lbl : company_default_tax_label();
        }

        $sub = $service->relationLoaded('subCategory')
            ? $service->getRelation('subCategory')
            : $service->subCategory()->first();
        if ($sub && $sub->tax_percentage !== null && $sub->tax_percentage !== '') {
            $lbl = $sub->tax_label;

            return ($lbl !== null && $lbl !== '') ? (string) $lbl : company_default_tax_label();
        }

        $cat = $service->relationLoaded('category')
            ? $service->getRelation('category')
            : $service->category()->first();
        if ($cat && $cat->tax_percentage !== null && $cat->tax_percentage !== '') {
            $lbl = $cat->tax_label;

            return ($lbl !== null && $lbl !== '') ? (string) $lbl : company_default_tax_label();
        }

        return company_default_tax_label();
    }
}











