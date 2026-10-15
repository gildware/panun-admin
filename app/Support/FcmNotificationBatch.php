<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Admin saves used to call Firebase once per device, in series, while the
 * database transaction was still open. A provider with several phones held
 * every modal save for several seconds. Messages are sent together after the
 * save has finished its own work.
 */
final class FcmNotificationBatch
{
    /** @var list<array<string, mixed>> */
    private static array $pending = [];

    private static bool $armed = false;

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function enqueue(?array $data): bool
    {
        if (! is_array($data) || $data === []) {
            return false;
        }

        $account = fcm_service_account_credentials();
        if (! self::accountIsUsable($account)) {
            return false;
        }

        // Artisan, queue workers, and tests run in the console. Flush there
        // immediately so a long-running worker does not hold messages until exit.
        if (app()->runningInConsole()) {
            return self::deliver([$data], $account);
        }

        self::$pending[] = $data;
        self::arm();

        return true;
    }

    private static function arm(): void
    {
        if (self::$armed) {
            return;
        }

        self::$armed = true;
        app()->terminating(static function (): void {
            $batch = self::$pending;
            self::$pending = [];
            self::$armed = false;
            if ($batch === []) {
                return;
            }

            // php artisan serve is one process. Sending Firebase here keeps the
            // next page load (the reload after the modal closes) waiting until
            // Google answers. Hand the batch to a separate process and return.
            if (! self::spawn($batch)) {
                self::deliver($batch, fcm_service_account_credentials());
            }
        });
    }

    /**
     * @param  list<array<string, mixed>>  $payloads
     */
    public static function deliverPayloads(array $payloads): bool
    {
        return self::deliver($payloads, fcm_service_account_credentials());
    }

    /**
     * @param  list<array<string, mixed>>  $payloads
     */
    private static function spawn(array $payloads): bool
    {
        $dir = storage_path('app/fcm-outbox');
        if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
            return false;
        }

        $path = $dir.'/'.bin2hex(random_bytes(16)).'.json';
        $json = json_encode($payloads);
        if (! is_string($json) || file_put_contents($path, $json, LOCK_EX) === false) {
            return false;
        }
        @chmod($path, 0600);

        if (! self::canSpawn()) {
            @unlink($path);

            return false;
        }

        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        if ($php === '' || ! is_file($artisan)) {
            @unlink($path);

            return false;
        }

        $shell = 'cd '.escapeshellarg(base_path()).' && '
            .escapeshellarg($php).' '.escapeshellarg($artisan).' fcm:deliver '.escapeshellarg($path);

        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen('start /B '.$shell, 'r'));

            return true;
        }

        // A new session so php artisan serve does not keep this request open
        // until Firebase answers. setsid is Linux; macOS uses perl POSIX::setsid.
        exec('command -v setsid >/dev/null 2>&1', $which, $whichCode);
        $launcher = $whichCode === 0
            ? 'setsid sh -c '.escapeshellarg($shell)
            : 'perl -MPOSIX -e '.escapeshellarg('POSIX::setsid(); exec @ARGV').' sh -c '.escapeshellarg($shell);

        exec($launcher.' > /dev/null 2>&1 &');

        return true;
    }

    private static function canSpawn(): bool
    {
        if (! function_exists('exec')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('exec', $disabled, true);
    }

    /**
     * @param  array<string, mixed>  $account
     */
    private static function accountIsUsable(array $account): bool
    {
        return filled($account['project_id'] ?? null)
            && filled($account['client_email'] ?? null)
            && filled($account['private_key'] ?? null);
    }

    /**
     * @param  list<array<string, mixed>>  $payloads
     * @param  array<string, mixed>  $account
     */
    private static function deliver(array $payloads, array $account): bool
    {
        if ($payloads === [] || ! self::accountIsUsable($account)) {
            return false;
        }

        $projectId = (string) $account['project_id'];
        $accessToken = getAccessToken((string) $account['client_email'], (string) $account['private_key']);
        if (! filled($accessToken)) {
            $error = $projectId === 'chat-e2e-test-project'
                ? 'Firebase is still using the chat E2E dummy service account. Upload the panun-kaergar-a162d service account JSON in Admin → Third Party → Firebase push.'
                : 'FCM OAuth access token could not be obtained. Check Firebase service account in admin third-party settings.';
            foreach ($payloads as $payload) {
                self::logPayload($payload, 'failed', null, $error);
            }

            return false;
        }

        $url = 'https://fcm.googleapis.com/v1/projects/'.$projectId.'/messages:send';
        $headers = [
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ];

        try {
            $responses = Http::pool(function ($pool) use ($payloads, $url, $headers) {
                foreach ($payloads as $index => $payload) {
                    $pool->as((string) $index)
                        ->withHeaders($headers)
                        ->connectTimeout(2)
                        ->timeout(5)
                        ->post($url, $payload);
                }
            });
        } catch (Throwable $exception) {
            foreach ($payloads as $payload) {
                self::logPayload($payload, 'failed', null, $exception->getMessage());
            }

            return false;
        }

        $allOk = true;
        foreach ($payloads as $index => $payload) {
            $response = $responses[(string) $index] ?? $responses[$index] ?? null;
            if ($response instanceof Response) {
                $ok = $response->successful();
                self::logPayload(
                    $payload,
                    $ok ? 'sent' : 'failed',
                    $response->status(),
                    $ok ? null : $response->body()
                );
                $allOk = $allOk && $ok;
                continue;
            }

            $allOk = false;
            self::logPayload(
                $payload,
                'failed',
                null,
                $response instanceof Throwable ? $response->getMessage() : 'FCM request was not sent.'
            );
        }

        return $allOk;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function logPayload(array $payload, string $status, ?int $httpStatus, ?string $error): void
    {
        log_push_notification_delivery([
            'fcm_token' => data_get($payload, 'message.token'),
            'topic' => data_get($payload, 'message.topic'),
            'notification_type' => data_get($payload, 'message.data.type'),
            'title' => data_get($payload, 'message.data.title'),
            'push_notification_id' => data_get($payload, 'message.data.push_notification_id') ?: null,
            'booking_id' => data_get($payload, 'message.data.booking_id') ?: null,
            'status' => $status,
            'http_status' => $httpStatus,
            'error_message' => $error,
        ]);
    }
}
