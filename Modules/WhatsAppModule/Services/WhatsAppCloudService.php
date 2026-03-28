<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppCloudService
{
    /** E.164 max length; Meta expects digits only (no +). */
    private const PHONE_DIGITS_MIN = 8;

    private const PHONE_DIGITS_MAX = 15;

    /**
     * Send plain text via WhatsApp Cloud API. Returns wa_message_id or null.
     */
    public function sendText(string $phone, string $body, ?string &$error = null, ?array &$graphContext = null): ?string
    {
        return $this->sendOutbound($phone, $body, null, $error, $graphContext);
    }

    /**
     * Strip to digits, apply default country prefix from WhatsApp business settings (same as booking notifications).
     *
     * National numbers often include a trunk "0" (e.g. PK 03xx…, UK 07xx…). n8n / Graph API expect full international
     * digits without +. If we prepend the country prefix without dropping that 0, the number is wrong (e.g. 920300…
     * instead of 92300…), which matches "works from n8n, not from admin".
     */
    public function normalizeRecipientPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        // International access prefix 00… (e.g. 0092… → 92…)
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
            if ($digits === '') {
                return null;
            }
        }

        $row = business_config(BookingWhatsAppNotificationService::SETTINGS_KEY, BookingWhatsAppNotificationService::SETTINGS_TYPE);
        $config = is_array($row?->live_values) ? $row->live_values : [];
        $prefix = preg_replace('/\D+/', '', (string) ($config['default_phone_prefix'] ?? '')) ?? '';

        if ($prefix !== '' && strlen($digits) <= 11 && !str_starts_with($digits, $prefix)) {
            if (str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }
            if ($digits === '') {
                return null;
            }
            $digits = $prefix . $digits;
        }

        $len = strlen($digits);
        if ($len < self::PHONE_DIGITS_MIN || $len > self::PHONE_DIGITS_MAX) {
            return null;
        }

        return $digits;
    }

    /**
     * Send outbound message via WhatsApp Cloud API.
     * If $mediaPath is provided (relative to public disk), uploads media and sends with optional caption.
     *
     * @param  array<string, mixed>|null  $graphContext  Filled with Graph API details for logging / admin JSON (no secrets).
     */
    public function sendOutbound(string $phone, string $body, ?string $mediaPath, ?string &$error = null, ?array &$graphContext = null): ?string
    {
        $graphContext = null;

        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        if (!$token || !$phoneId) {
            Log::warning('WhatsApp outbound not configured (missing services.whatsapp_cloud config).');
            $error = 'missing_config';
            $graphContext = ['stage' => 'config', 'detail' => 'missing_token_or_phone_id'];

            return null;
        }

        $rawInputPhone = $phone;
        $normalized = $this->normalizeRecipientPhone($phone);
        if ($normalized === null) {
            $error = 'invalid_phone';
            Log::warning('WhatsApp outbound: invalid or empty phone after normalize', [
                'raw_input' => $rawInputPhone,
                'digits_len' => strlen(preg_replace('/\D+/', '', $rawInputPhone) ?? ''),
            ]);
            $graphContext = [
                'stage' => 'validate',
                'raw_input' => $rawInputPhone,
                'normalized' => null,
            ];

            return null;
        }

        if ($normalized !== preg_replace('/\D+/', '', $rawInputPhone)) {
            Log::info('WhatsApp outbound: phone normalized for Graph API', [
                'raw_input' => $rawInputPhone,
                'to' => $normalized,
            ]);
        }

        $phone = $normalized;

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
                        $uploadJson = $uploadResponse->json();
                        Log::warning('WhatsApp Cloud media upload failed', [
                            'phone' => $phone,
                            'http_status' => $uploadResponse->status(),
                            'graph_response' => $uploadJson ?? $uploadResponse->body(),
                        ]);
                        $graphContext = [
                            'stage' => 'media_upload',
                            'http_status' => $uploadResponse->status(),
                            'graph_response' => $uploadJson,
                            'graph_body_raw' => is_array($uploadJson) ? null : $uploadResponse->body(),
                        ];

                        return null;
                    }

                    $uploadPayload = $uploadResponse->json();
                    $mediaId = $uploadPayload['id'] ?? null;
                    if (!$mediaId) {
                        $error = 'media_id_missing';
                        Log::warning('WhatsApp Cloud media upload missing id', ['response' => $uploadPayload]);
                        $graphContext = [
                            'stage' => 'media_upload',
                            'http_status' => $uploadResponse->status(),
                            'graph_response' => $uploadPayload,
                        ];

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

            $respPayload = $response->json();
            $httpStatus = $response->status();

            if ($response->failed()) {
                $error = 'status:' . $httpStatus . ' body:' . $response->body();
                Log::warning('WhatsApp Cloud send failed (Graph API)', [
                    'phone' => $phone,
                    'payload_type' => $payload['type'] ?? null,
                    'http_status' => $httpStatus,
                    'graph_response' => $respPayload ?? $response->body(),
                ]);
                $graphContext = [
                    'stage' => 'messages',
                    'http_status' => $httpStatus,
                    'to' => $phone,
                    'payload_type' => $payload['type'] ?? null,
                    'graph_response' => $respPayload,
                    'graph_body_raw' => is_array($respPayload) ? null : $response->body(),
                ];

                return null;
            }

            $waId = $respPayload['messages'][0]['id'] ?? null;
            if (!$waId) {
                $error = 'missing_wa_message_id';
                Log::warning('WhatsApp Cloud: HTTP success but no messages[0].id in Graph response', [
                    'phone' => $phone,
                    'http_status' => $httpStatus,
                    'graph_response' => $respPayload,
                ]);
                $graphContext = [
                    'stage' => 'messages',
                    'http_status' => $httpStatus,
                    'to' => $phone,
                    'graph_response' => $respPayload,
                ];

                return null;
            }

            // Full Meta response: contacts (input → wa_id), message id, etc. — use for delivery troubleshooting.
            Log::info('WhatsApp Cloud Graph API send accepted', [
                'phone' => $phone,
                'http_status' => $httpStatus,
                'wa_message_id' => $waId,
                'contacts' => $respPayload['contacts'] ?? null,
                'messaging_product' => $respPayload['messaging_product'] ?? null,
                'graph_response' => $respPayload,
            ]);

            $graphContext = [
                'stage' => 'messages',
                'http_status' => $httpStatus,
                'to' => $phone,
                'wa_message_id' => $waId,
                'contacts' => $respPayload['contacts'] ?? null,
                'messaging_product' => $respPayload['messaging_product'] ?? null,
            ];

            return $waId;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::warning('WhatsApp Cloud send exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            $graphContext = [
                'stage' => 'exception',
                'to' => $phone,
                'error' => $e->getMessage(),
            ];

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
