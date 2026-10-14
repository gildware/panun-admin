<?php

namespace App\Console\Commands;

use App\Support\FcmNotificationBatch;
use Illuminate\Console\Command;

class DeliverFcmBatchCommand extends Command
{
    protected $signature = 'fcm:deliver {file : JSON file of Firebase message payloads}';

    protected $description = 'Send a Firebase batch outside the web request that queued it';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        $dir = realpath(storage_path('app/fcm-outbox'));
        $real = realpath($path);
        if ($dir === false || $real === false || ! str_starts_with($real, $dir.DIRECTORY_SEPARATOR)) {
            $this->error('Refusing to read a payload file outside the FCM outbox.');

            return self::FAILURE;
        }

        $json = file_get_contents($real);
        @unlink($real);
        $payloads = json_decode((string) $json, true);
        if (! is_array($payloads)) {
            return self::FAILURE;
        }

        FcmNotificationBatch::deliverPayloads($payloads);

        return self::SUCCESS;
    }
}
