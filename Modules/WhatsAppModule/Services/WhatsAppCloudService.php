<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppCloudService
{
    /**
     * Send plain text via WhatsApp Cloud API. Returns wa_message_id or null.
     */
    public function sendText(string $phone, string $body, ?string &$error = null): ?string
    {
        return $this->sendOutbound($phone, $body, null, $error);
    }

    /**
     * Send outbound message via WhatsApp Cloud API.
     * If $mediaPath is provided (relative to public disk), uploads media and sends with optional caption.
     */
    public function sendOutbound(string $phone, string $body, ?string $mediaPath, ?string &$error = null): ?string
    {
        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        if (!$token || !$phoneId) {
            Log::warning('WhatsApp outbound not configured (missing services.whatsapp_cloud config).');
            $error = 'missing_config';

            return null;
        }

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');
        $messagesUrl = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
            ];

            if ($mediaPath) {
                $fullPath = Storage::disk('public')->path($mediaPath);
                if (!is_file($fullPath)) {
                    Log::warning('WhatsApp media file not found', ['path' => $fullPath]);
                } else {
                    $mediaUrl = "https://graph.facebook.com/{$version}/{$phoneId}/media";
                    $uploadResponse = Http::withToken($token)
                        ->acceptJson()
                        ->asMultipart()
                        ->attach('file', fopen($fullPath, 'r'), basename($fullPath))
                        ->post($mediaUrl, [
                            'messaging_product' => 'whatsapp',
                        ]);

                    if ($uploadResponse->failed()) {
                        $error = 'media_upload_status:' . $uploadResponse->status() . ' body:' . $uploadResponse->body();
                        Log::warning('WhatsApp Cloud media upload failed', [
                            'phone' => $phone,
                            'status' => $uploadResponse->status(),
                            'body' => $uploadResponse->body(),
                        ]);

                        return null;
                    }

                    $uploadPayload = $uploadResponse->json();
                    $mediaId = $uploadPayload['id'] ?? null;
                    if (!$mediaId) {
                        $error = 'media_id_missing';
                        Log::warning('WhatsApp Cloud media upload missing id', ['response' => $uploadPayload]);

                        return null;
                    }

                    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                    $mediaType = $this->mediaTypeFromExtension($ext);
                    $filename = basename($fullPath);

                    if ($mediaType === 'document') {
                        $payload['type'] = 'document';
                        $payload['document'] = [
                            'id' => $mediaId,
                            'filename' => $filename,
                        ];
                        if ($body !== '') {
                            $payload['document']['caption'] = $body;
                        }
                    } elseif ($mediaType === 'video') {
                        $payload['type'] = 'video';
                        $payload['video'] = ['id' => $mediaId];
                        if ($body !== '') {
                            $payload['video']['caption'] = $body;
                        }
                    } elseif ($mediaType === 'audio') {
                        $payload['type'] = 'audio';
                        $payload['audio'] = ['id' => $mediaId];
                    } else {
                        $payload['type'] = 'image';
                        $payload['image'] = ['id' => $mediaId];
                        if ($body !== '') {
                            $payload['image']['caption'] = $body;
                        }
                    }
                }
            }

            if (!isset($payload['type'])) {
                $payload['type'] = 'text';
                $payload['text'] = [
                    'preview_url' => true,
                    'body' => $body,
                ];
            }

            $response = Http::withToken($token)
                ->acceptJson()
                ->post($messagesUrl, $payload);

            if ($response->failed()) {
                $error = 'status:' . $response->status() . ' body:' . $response->body();
                Log::warning('WhatsApp Cloud send failed', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $respPayload = $response->json();
            $waId = $respPayload['messages'][0]['id'] ?? null;

            Log::info('WhatsApp Cloud send ok', [
                'phone' => $phone,
                'status' => $response->status(),
                'wa_message_id' => $waId,
            ]);

            return $waId;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::warning('WhatsApp Cloud send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function mediaTypeFromExtension(string $ext): string
    {
        $docExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip'];
        if (in_array($ext, $docExtensions, true)) {
            return 'document';
        }
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        if (in_array($ext, $imageExtensions, true)) {
            return 'image';
        }
        $videoExtensions = ['mp4', '3gp', 'avi', 'mov', 'webm', 'mkv'];
        if (in_array($ext, $videoExtensions, true)) {
            return 'video';
        }
        $audioExtensions = ['mp3', 'ogg', 'wav', 'm4a', 'aac', 'oga'];
        if (in_array($ext, $audioExtensions, true)) {
            return 'audio';
        }

        return 'document';
    }
}
