<?php

use App\Http\Controllers\LandingController;
use App\Services\ImageProxyAllowlist;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/image-proxy', function () {
    $url = request('url');
    if (!$url) {
        abort(400, 'Missing url parameter');
    }

    $parsed = parse_url($url);
    $scheme = strtolower($parsed['scheme'] ?? '');
    $host = strtolower($parsed['host'] ?? '');

    if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
        abort(403, 'Invalid url');
    }

    $allowedHosts = ImageProxyAllowlist::hosts();

    if ($allowedHosts === []) {
        abort(403, 'Image proxy allowlist not configured');
    }

    if (!in_array($host, $allowedHosts, true)) {
        abort(403, 'Host not allowed');
    }

    $resolvedIp = gethostbyname($host);
    if ($resolvedIp === $host || !filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        abort(403, 'Host resolves to a private or reserved address');
    }

    $response = Http::withHeaders([
        'User-Agent' => 'Laravel-Image-Proxy'
    ])->timeout(10)->get($url);

    return response($response->body(), $response->status())
        ->header('Content-Type', $response->header('Content-Type'));
});

Route::get('lang/{locale}', [LandingController::class, 'lang'])->name('lang');
Route::get('/', [LandingController::class, 'home'])->name('home');
Route::get('page/contact-us', [LandingController::class, 'contactUs'])->name('page.contact-us');

Route::get('business-page/{slug}', [LandingController::class, 'dynamicPage'])->name('business.page.dynamic');

Route::get('maintenance-mode', [LandingController::class, 'maintenanceMode'])->name('maintenance-mode');
Route::post('subscribe-newsletter',[LandingController::class, 'subscribeNewsletter'])->name('subscribe-newsletter');

Route::get('/storage/{path}', function (string $path) {
    $path = str_replace(['..', '\\'], '', $path);

    foreach (\App\Support\StoragePathPrefix::keyVariants($path) as $candidate) {
        $fullPath = storage_path('app/public/'.$candidate);
        if (is_file($fullPath)) {
            return response()->file($fullPath);
        }
    }

    abort(404);
})->where('path', '.*');

Route::fallback(function () {
    return redirect('admin/auth/login');
});

Route::get('test', function () {
    //
});



