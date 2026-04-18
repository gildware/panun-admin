<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Http\UploadedFile;
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
    public function sendText(string $phone, string $body, ?string &$error = null, ?array &$graphContext = null, ?string $replyToMessageId = null): ?string
    {
        return $this->sendOutbound($phone, $body, null, $error, $graphContext, $replyToMessageId);
    }

    /**
     * Strip to digits; for short local-style numbers, optionally prepend a default country prefix (configurable).
     *
     * National numbers often include a trunk "0" (e.g. PK 03xx…, UK 07xx…). n8n / Graph API expect full international
     * digits without +. If we prepend the country prefix without dropping that 0, the number is wrong (e.g. 920300…
     * instead of 92300…), which matches "works from n8n, not from admin".
     */
    public function normalizeRecipientPhone(string $phone, ?array $bookingAutomationPrefix = null): ?string
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

        if (is_array($bookingAutomationPrefix)) {
            $applyPrefix = (bool) ($bookingAutomationPrefix['apply_default_phone_prefix'] ?? true);
            $prefix = preg_replace('/\D+/', '', (string) ($bookingAutomationPrefix['default_phone_prefix'] ?? '')) ?? '';
        } else {
            $applyPrefix = (bool) config('services.whatsapp_cloud.auto_prefix_enabled', true);
            $prefix = (string) config('services.whatsapp_cloud.default_country_prefix', '');
            $prefix = preg_replace('/\D+/', '', $prefix) ?? '';
        }

        // WhatsApp Cloud API expects full international digits (no '+', digits only).
        // Default project rule: if we receive a 10-digit number, prefix it (e.g. IN 91xxxxxxxxxx).
        // If we receive 12 digits that already start with the prefix (e.g. 91xxxxxxxxxx), keep as-is.
        // If the input is "+91..." the "+" is already removed by digit stripping above.
        $len = strlen($digits);
        if ($applyPrefix && $prefix !== '') {
            if ($len === 12 && str_starts_with($digits, $prefix)) {
                // already normalized
            } elseif ($len === 11 && str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
                $len = strlen($digits);
                if ($len === 10) {
                    $digits = $prefix . $digits;
                    $len = strlen($digits);
                }
            } elseif ($len === 10) {
                $digits = $prefix . $digits;
                $len = strlen($digits);
            }
        }

        if ($len < self::PHONE_DIGITS_MIN || $len > self::PHONE_DIGITS_MAX) {
            return null;
        }

        return $digits;
    }

    /**
     * Meta downloads template header media from the link you pass to Graph API. URLs that point at
     * localhost or private/reserved IPs always fail (e.g. error 131053 "127.0.0.1 is private").
     */
    public static function isTemplateHeaderMediaLinkLikelyFetchableByMeta(string $url): bool
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === null || $host === '') {
            return false;
        }
        $hostLower = strtolower($host);
        if (in_array($hostLower, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            return false;
        }
        $ip = filter_var($host, FILTER_VALIDATE_IP);
        if ($ip !== false) {
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return true;
    }

    /**
     * Send outbound message via WhatsApp Cloud API.
     * If $mediaPath is provided (relative to public disk), uploads media and sends with optional caption.
     *
     * @param  array<string, mixed>|null  $graphContext  Filled with Graph API details for logging / admin JSON (no secrets).
     */
    public function sendOutbound(string $phone, string $body, ?string $mediaPath, ?string &$error = null, ?array &$graphContext = null, ?string $replyToMessageId = null): ?string
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

            if ($replyToMessageId !== null && $replyToMessageId !== '') {
                $payload['context'] = ['message_id' => $replyToMessageId];
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

    /**
     * True if Graph API error payload indicates the recipient cannot be messaged on WhatsApp (not registered, invalid, etc.).
     *
     * @param  array<string, mixed>|null  $json
     */
    /**
     * Human-readable message from a failed Graph call (template send, etc.).
     *
     * @param  array<string, mixed>|null  $graphContext
     */
    public static function graphErrorMessageFromContext(?array $graphContext): ?string
    {
        if ($graphContext === null) {
            return null;
        }
        $gr = $graphContext['graph_response'] ?? null;
        if (!is_array($gr)) {
            return null;
        }
        $e = $gr['error'] ?? null;
        if (!is_array($e)) {
            return null;
        }
        $msg = (string) ($e['error_user_msg'] ?? $e['message'] ?? '');
        $code = isset($e['code']) ? (string) $e['code'] : '';
        if ($msg !== '') {
            return $code !== '' ? '(' . $code . ') ' . $msg : $msg;
        }

        return null;
    }

    public function graphErrorIndicatesRecipientNotOnWhatsApp(?array $json): bool
    {
        if (!is_array($json) || !isset($json['error']) || !is_array($json['error'])) {
            return false;
        }
        $e = $json['error'];
        $code = (int) ($e['code'] ?? 0);
        $msg = strtolower((string) ($e['message'] ?? ''));

        if (str_contains($msg, 'not a valid whatsapp') || str_contains($msg, 'is not a valid whatsapp')) {
            return true;
        }
        if (str_contains($msg, 'invalid') && (str_contains($msg, 'recipient') || str_contains($msg, 'phone') || str_contains($msg, 'parameter'))) {
            return true;
        }
        // Policy / session window (user may still be on WhatsApp — do not show “invalid number”).
        if (in_array($code, [131047, 131049, 131048], true)) {
            return false;
        }
        // Likely “no WhatsApp user” / bad address (not WABA lock 131031).
        $recipientCodes = [131026, 131028, 132000, 132001, 132005, 132007, 132012, 133010];
        if (in_array($code, $recipientCodes, true)) {
            return true;
        }

        return false;
    }

    /**
     * Use Cloud API POST /{phone-number-id}/contacts to see if Meta recognises the number as a WhatsApp user.
     * Does not deliver a message to the customer (unlike {@see probeRecipientAcceptsWhatsApp}).
     *
     * @param  array<string, mixed>|null  $graphContext
     * @return bool True when the first contact returns status "valid"
     */
    public function checkRecipientRegisteredViaContacts(string $normalizedDigits, ?string &$error = null, ?array &$graphContext = null): bool
    {
        $graphContext = null;
        $error = null;

        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        if (!$token || !$phoneId) {
            $error = 'missing_config';

            return false;
        }

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');
        $contactsUrl = "https://graph.facebook.com/{$version}/{$phoneId}/contacts";

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(25)
                ->post($contactsUrl, [
                    'blocking' => 'wait',
                    'contacts' => [$normalizedDigits],
                    'force_check' => true,
                ]);

            $respPayload = $response->json();
            $graphContext = [
                'http_status' => $response->status(),
                'graph_response' => is_array($respPayload) ? $respPayload : ['raw' => $response->body()],
            ];

            if (!$response->successful()) {
                if (is_array($respPayload) && $this->graphErrorIndicatesRecipientNotOnWhatsApp($respPayload)) {
                    $error = 'not_on_whatsapp';
                } else {
                    $error = 'graph_rejected';
                }

                return false;
            }

            $contacts = $respPayload['contacts'] ?? null;
            if (!is_array($contacts) || $contacts === []) {
                $error = 'invalid_response';

                return false;
            }

            $first = $contacts[0];
            if (!is_array($first)) {
                $error = 'invalid_response';

                return false;
            }

            $status = strtolower((string) ($first['status'] ?? ''));
            if ($status === 'valid') {
                return true;
            }

            if ($status === 'invalid') {
                $error = 'not_on_whatsapp';

                return false;
            }

            $error = 'unknown_contact_status';

            return false;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp checkRecipientRegisteredViaContacts failed.', [
                'phone' => $normalizedDigits,
                'error' => $e->getMessage(),
            ]);
            $error = 'exception';
            $graphContext = ['exception' => $e->getMessage()];

            return false;
        }
    }

    /**
     * POST a minimal text via Cloud API to confirm Meta accepts the recipient (implies they can receive WhatsApp from this WABA).
     * $normalizedDigits must be the output of normalizeRecipientPhone().
     *
     * @param  array<string, mixed>|null  $graphContext
     * @return bool True if HTTP success and a message id is returned
     */
    public function probeRecipientAcceptsWhatsApp(string $normalizedDigits, ?string &$error = null, ?array &$graphContext = null): bool
    {
        $graphContext = null;
        $error = null;

        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        if (!$token || !$phoneId) {
            $error = 'missing_config';

            return false;
        }

        $probeText = (string) config('services.whatsapp_cloud.open_chat_probe_text', "\u{2060}");
        if (trim($probeText) === '') {
            $probeText = "\u{2060}";
        }

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');
        $messagesUrl = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(25)
                ->post($messagesUrl, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $normalizedDigits,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $probeText,
                    ],
                ]);

            $respPayload = $response->json();
            $graphContext = [
                'http_status' => $response->status(),
                'graph_response' => is_array($respPayload) ? $respPayload : ['raw' => $response->body()],
            ];

            if ($response->successful()) {
                $mid = $respPayload['messages'][0]['id'] ?? null;

                return $mid !== null && $mid !== '';
            }

            if (is_array($respPayload) && $this->graphErrorIndicatesRecipientNotOnWhatsApp($respPayload)) {
                $error = 'not_on_whatsapp';
            } else {
                $error = 'graph_rejected';
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp probeRecipientAcceptsWhatsApp failed.', [
                'phone' => $normalizedDigits,
                'error' => $e->getMessage(),
            ]);
            $error = 'exception';
            $graphContext = ['exception' => $e->getMessage()];

            return false;
        }
    }

    /**
     * Send or remove a reaction on a specific message (WhatsApp Cloud API).
     * Pass empty $emoji to remove the business reaction on that message.
     */
    public function sendReaction(string $phone, string $targetWaMessageId, string $emoji, ?string &$error = null, ?array &$graphContext = null): bool
    {
        $graphContext = null;

        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        if (!$token || !$phoneId) {
            Log::warning('WhatsApp reaction: missing services.whatsapp_cloud config.');
            $error = 'missing_config';
            $graphContext = ['stage' => 'config'];

            return false;
        }

        $normalized = $this->normalizeRecipientPhone($phone);
        if ($normalized === null) {
            $error = 'invalid_phone';
            $graphContext = ['stage' => 'validate'];

            return false;
        }

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');
        $messagesUrl = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $normalized,
            'type' => 'reaction',
            'reaction' => [
                'message_id' => $targetWaMessageId,
                'emoji' => $emoji,
            ],
        ];

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->post($messagesUrl, $payload);

            $respPayload = $response->json();
            if ($response->failed()) {
                $error = 'status:' . $response->status() . ' body:' . $response->body();
                Log::warning('WhatsApp Cloud reaction failed', [
                    'phone' => $normalized,
                    'http_status' => $response->status(),
                    'graph_response' => $respPayload ?? $response->body(),
                ]);
                $graphContext = [
                    'stage' => 'reaction',
                    'http_status' => $response->status(),
                    'graph_response' => $respPayload,
                ];

                return false;
            }

            $graphContext = [
                'stage' => 'reaction',
                'http_status' => $response->status(),
                'graph_response' => $respPayload,
            ];

            return true;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::warning('WhatsApp Cloud reaction exception', ['error' => $e->getMessage()]);
            $graphContext = ['stage' => 'exception', 'error' => $e->getMessage()];

            return false;
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

    /**
     * Download inbound Cloud API media to the public disk (same behaviour as legacy internal sync).
     */
    public function downloadInboundMediaToPublicDisk(?string $mediaId, ?string $directUrl, ?string $mimeType): ?string
    {
        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        if (!$token || !$phoneId) {
            Log::warning('WhatsApp inbound media: missing cloud API config.');

            return null;
        }

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');

        try {
            $url = $directUrl;
            $resolvedMime = $mimeType;

            if ($mediaId && !$url) {
                $metaResp = Http::withToken($token)
                    ->acceptJson()
                    ->get("https://graph.facebook.com/{$version}/{$mediaId}");

                if ($metaResp->failed()) {
                    Log::warning('WhatsApp inbound media: failed to fetch media metadata', [
                        'media_id' => $mediaId,
                        'status' => $metaResp->status(),
                    ]);

                    return null;
                }

                $meta = $metaResp->json();
                $url = $meta['url'] ?? null;
                $resolvedMime = $resolvedMime ?: ($meta['mime_type'] ?? null);
            }

            if (!$url) {
                return null;
            }

            $fileResp = Http::withToken($token)->get($url);
            if ($fileResp->failed()) {
                Log::warning('WhatsApp inbound media: failed to download file', [
                    'media_id' => $mediaId,
                    'status' => $fileResp->status(),
                ]);

                return null;
            }

            $contentType = strtolower($resolvedMime ?: ($fileResp->header('Content-Type') ?? ''));
            $ext = 'bin';
            if (str_starts_with($contentType, 'image/')) {
                $ext = match ($contentType) {
                    'image/jpeg', 'image/jpg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    default => 'jpg',
                };
            } elseif ($contentType === 'application/pdf') {
                $ext = 'pdf';
            } elseif (str_starts_with($contentType, 'video/')) {
                $ext = 'mp4';
            } elseif (str_starts_with($contentType, 'audio/')) {
                $ext = 'mp3';
            }

            $filename = 'in_' . ($mediaId ?: uniqid('', true)) . '.' . $ext;
            $path = 'whatsapp_attachments/' . $filename;
            Storage::disk('public')->put($path, $fileResp->body());

            return $path;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp inbound media: exception', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send quick-reply buttons (max 3). Titles max 20 chars each per Meta rules.
     *
     * @param  array<int, array{id: string, title: string}>  $buttons
     */
    public function sendInteractiveButtons(
        string $phone,
        string $bodyText,
        array $buttons,
        ?string &$error = null,
        ?array &$graphContext = null
    ): ?string {
        $graphContext = null;
        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        if (!$token || !$phoneId) {
            $error = 'missing_config';

            return null;
        }

        $normalized = $this->normalizeRecipientPhone($phone);
        if ($normalized === null) {
            $error = 'invalid_phone';

            return null;
        }

        $buttons = array_values(array_slice($buttons, 0, 3));
        $actionButtons = [];
        foreach ($buttons as $b) {
            $id = (string) ($b['id'] ?? '');
            $title = mb_substr((string) ($b['title'] ?? ''), 0, 20);
            if ($id === '' || $title === '') {
                continue;
            }
            $actionButtons[] = [
                'type' => 'reply',
                'reply' => ['id' => $id, 'title' => $title],
            ];
        }

        if ($actionButtons === []) {
            $error = 'no_buttons';

            return null;
        }

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');
        $messagesUrl = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $normalized,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => mb_substr($bodyText, 0, 1024)],
                'action' => ['buttons' => $actionButtons],
            ],
        ];

        try {
            $response = Http::withToken($token)->acceptJson()->post($messagesUrl, $payload);
            $respPayload = $response->json();
            if ($response->failed()) {
                $error = 'status:' . $response->status();
                $graphContext = ['graph_response' => $respPayload];

                return null;
            }

            return $respPayload['messages'][0]['id'] ?? null;
        } catch (\Throwable $e) {
            $error = $e->getMessage();

            return null;
        }
    }

    /**
     * Single CTA URL interactive message (session). display_text and body are truncated per Meta limits.
     */
    public function sendInteractiveCtaUrl(
        string $phone,
        string $bodyText,
        string $displayText,
        string $url,
        ?string &$error = null,
        ?array &$graphContext = null
    ): ?string {
        $graphContext = null;
        $error = null;
        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        if (!$token || !$phoneId) {
            $error = 'missing_config';

            return null;
        }

        $normalized = $this->normalizeRecipientPhone($phone);
        if ($normalized === null) {
            $error = 'invalid_phone';

            return null;
        }

        $url = trim($url);
        if ($url === '' || !str_starts_with($url, 'https://')) {
            $error = 'invalid_cta_url';

            return null;
        }

        $bodyText = trim($bodyText) === '' ? ' ' : mb_substr($bodyText, 0, 1024);
        $displayText = mb_substr(trim($displayText), 0, 20);

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');
        $messagesUrl = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $normalized,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'cta_url',
                'body' => ['text' => $bodyText],
                'action' => [
                    'name' => 'cta_url',
                    'parameters' => [
                        'display_text' => $displayText,
                        'url' => mb_substr($url, 0, 2000),
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($token)->acceptJson()->post($messagesUrl, $payload);
            $respPayload = $response->json();
            if ($response->failed()) {
                $error = 'status:'.$response->status();
                $graphContext = ['graph_response' => $respPayload];

                return null;
            }

            return $respPayload['messages'][0]['id'] ?? null;
        } catch (\Throwable $e) {
            $error = $e->getMessage();

            return null;
        }
    }

    /**
     * Single CTA phone interactive message (session). Same 24h window as {@see sendInteractiveCtaUrl}.
     * display_text max 20 chars per Meta; body truncated to 1024.
     *
     * @param  string  $phoneE164  E.164 with leading + (e.g. +918899881555)
     */
    public function sendInteractiveCtaPhone(
        string $phone,
        string $bodyText,
        string $displayText,
        string $phoneE164,
        ?string &$error = null,
        ?array &$graphContext = null
    ): ?string {
        $graphContext = null;
        $error = null;
        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        if (!$token || !$phoneId) {
            $error = 'missing_config';

            return null;
        }

        $normalized = $this->normalizeRecipientPhone($phone);
        if ($normalized === null) {
            $error = 'invalid_phone';

            return null;
        }

        $phoneE164 = trim($phoneE164);
        if ($phoneE164 === '' || !preg_match('/^\+[1-9]\d{6,14}$/', $phoneE164)) {
            $error = 'invalid_cta_phone';

            return null;
        }

        $bodyText = trim($bodyText) === '' ? ' ' : mb_substr($bodyText, 0, 1024);
        $displayText = mb_substr(trim($displayText), 0, 20);

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');
        $messagesUrl = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $normalized,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'cta_phone',
                'body' => ['text' => $bodyText],
                'action' => [
                    'name' => 'cta_phone',
                    'parameters' => [
                        'display_text' => $displayText,
                        'phone_number' => $phoneE164,
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($token)->acceptJson()->post($messagesUrl, $payload);
            $respPayload = $response->json();
            if ($response->failed()) {
                $error = 'status:'.$response->status();
                $graphContext = ['graph_response' => $respPayload];

                return null;
            }

            return $respPayload['messages'][0]['id'] ?? null;
        } catch (\Throwable $e) {
            $error = $e->getMessage();

            return null;
        }
    }

    /**
     * Fetch all message templates for the configured WhatsApp Business Account (paginated on Graph).
     *
     * @return array{0: array<int, array<string, mixed>>, 1: ?string} [templates, error]
     */
    public function fetchMessageTemplates(?string &$error = null): array
    {
        $error = null;
        $token = (string) config('services.whatsapp_cloud.token');
        $wabaId = (string) config('services.whatsapp_cloud.waba_id');
        if ($token === '' || $wabaId === '') {
            $error = 'missing_waba_or_token';

            return [[], $error];
        }

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');
        $all = [];
        $url = "https://graph.facebook.com/{$version}/{$wabaId}/message_templates";

        try {
            while ($url) {
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->get($url, [
                        'fields' => 'name,status,language,category,components,id,parameter_format',
                        'limit' => 100,
                    ]);

                if ($response->failed()) {
                    $error = 'http_' . $response->status() . ':' . $response->body();
                    Log::warning('WhatsApp fetch templates failed', ['detail' => $error]);

                    return [[], $error];
                }

                $payload = $response->json();
                $all = array_merge($all, $payload['data'] ?? []);
                $next = $payload['paging']['next'] ?? null;
                $url = is_string($next) ? $next : null;
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::warning('WhatsApp fetch templates exception', ['error' => $error]);

            return [[], $error];
        }

        return [$all, null];
    }

    /**
     * Graph `message_templates` rows return `language` as a string code (e.g. en_GB), or as an object
     * with `code` / `locale` (API versions differ). Casting an array to string yields wrong values and
     * breaks matching and sends (#132001).
     *
     * @param  array<string, mixed>  $row
     */
    public static function languageCodeFromGraphTemplateRow(array $row): string
    {
        $lang = $row['language'] ?? null;
        if (is_string($lang)) {
            return trim($lang);
        }
        if (is_array($lang)) {
            $code = $lang['code'] ?? $lang['locale'] ?? $lang['language'] ?? '';

            return is_string($code) ? trim($code) : '';
        }
        if (is_scalar($lang)) {
            return trim((string) $lang);
        }

        return '';
    }

    /**
     * Send a template message.
     *
     * @param  array<int, string>  $bodyParameters  Ordered values: positional {{1}}…{{n}}, or parallel to named_param_names when $bodyPlan is named.
     * @param  array<string, mixed>|null  $bodyPlan  From {@see resolveBodyParameterPlanFromTemplate()}; null means all-positional (legacy).
     * @param  array<string, mixed>|null  $headerTextPlan  From {@see resolveHeaderTextParameterPlanFromTemplate()}; null means all-positional.
     */
    public function sendTemplateMessage(
        string $phone,
        string $templateName,
        string $languageCode,
        array $bodyParameters,
        ?string &$error = null,
        ?array &$graphContext = null,
        array $headerTextParameters = [],
        ?string $headerMediaUrl = null,
        ?string $headerMediaFormat = null,
        ?array $bodyPlan = null,
        ?array $headerTextPlan = null,
    ): ?string {
        $graphContext = null;
        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        if ($token === '' || $phoneId === '') {
            $error = 'missing_config';
            $graphContext = ['stage' => 'config'];

            return null;
        }

        $normalized = $this->normalizeRecipientPhone($phone);
        if ($normalized === null) {
            $error = 'invalid_phone';
            $graphContext = ['stage' => 'validate'];

            return null;
        }

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');
        $messagesUrl = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        $templatePayload = [
            'name' => $templateName,
            'language' => ['code' => $languageCode],
        ];

        $components = [];

        if ($headerTextParameters !== []) {
            $headerParamsForApi = [];
            if ($headerTextPlan !== null && ($headerTextPlan['format'] ?? '') === 'named' && !empty($headerTextPlan['named_param_names'])) {
                foreach ($headerTextPlan['named_param_names'] as $i => $paramName) {
                    $headerParamsForApi[] = [
                        'type' => 'text',
                        'parameter_name' => $paramName,
                        'text' => $headerTextParameters[$i] ?? '',
                    ];
                }
            } else {
                foreach ($headerTextParameters as $text) {
                    $headerParamsForApi[] = ['type' => 'text', 'text' => $text];
                }
            }
            $components[] = [
                'type' => 'header',
                'parameters' => $headerParamsForApi,
            ];
        } elseif ($headerMediaUrl !== null && $headerMediaUrl !== '' && $headerMediaFormat !== null && $headerMediaFormat !== '') {
            $fmt = strtoupper($headerMediaFormat);
            $paramType = match ($fmt) {
                'VIDEO' => 'video',
                'DOCUMENT' => 'document',
                default => 'image',
            };
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => $paramType,
                        $paramType => ['link' => $headerMediaUrl],
                    ],
                ],
            ];
        }

        $bodyParamsForApi = [];
        if ($bodyParameters !== []) {
            if ($bodyPlan !== null && ($bodyPlan['format'] ?? '') === 'named' && !empty($bodyPlan['named_param_names'])) {
                foreach ($bodyPlan['named_param_names'] as $i => $paramName) {
                    $bodyParamsForApi[] = [
                        'type' => 'text',
                        'parameter_name' => $paramName,
                        'text' => $bodyParameters[$i] ?? '',
                    ];
                }
            } else {
                foreach ($bodyParameters as $text) {
                    $bodyParamsForApi[] = ['type' => 'text', 'text' => $text];
                }
            }
        }
        if ($bodyParamsForApi !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => $bodyParamsForApi,
            ];
        }

        if ($components !== []) {
            $templatePayload['components'] = $components;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $normalized,
            'type' => 'template',
            'template' => $templatePayload,
        ];

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->post($messagesUrl, $payload);

            $respPayload = $response->json();
            $httpStatus = $response->status();

            if ($response->failed()) {
                $error = 'status:' . $httpStatus . ' body:' . $response->body();
                Log::warning('WhatsApp template send failed', [
                    'phone' => $normalized,
                    'template' => $templateName,
                    'http_status' => $httpStatus,
                    'graph_response' => $respPayload ?? $response->body(),
                ]);
                $graphContext = [
                    'stage' => 'messages',
                    'http_status' => $httpStatus,
                    'graph_response' => $respPayload,
                ];

                return null;
            }

            $waId = $respPayload['messages'][0]['id'] ?? null;
            if (!$waId) {
                $error = 'missing_wa_message_id';
                $graphContext = ['stage' => 'messages', 'graph_response' => $respPayload];

                return null;
            }

            $graphContext = [
                'stage' => 'messages',
                'http_status' => $httpStatus,
                'wa_message_id' => $waId,
            ];

            return $waId;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $graphContext = ['stage' => 'exception', 'error' => $error];

            return null;
        }
    }

    /**
     * Max numbered placeholder index in BODY text, e.g. {{1}}, {{2}}.
     */
    public static function countBodyPlaceholdersFromComponents(?array $components): int
    {
        if (!$components) {
            return 0;
        }
        $max = 0;
        foreach ($components as $component) {
            if (strtoupper((string) ($component['type'] ?? '')) !== 'BODY') {
                continue;
            }
            $text = (string) ($component['text'] ?? '');
            if (preg_match_all('/\{\{(\d+)\}\}/', $text, $m)) {
                foreach ($m[1] as $n) {
                    $max = max($max, (int) $n);
                }
            }
        }

        return $max;
    }

    /**
     * Numbered placeholders {{1}} in HEADER when format is TEXT.
     */
    public static function countHeaderTextPlaceholdersFromComponents(?array $components): int
    {
        if (!$components) {
            return 0;
        }
        foreach ($components as $component) {
            if (!is_array($component)) {
                continue;
            }
            if (strtoupper((string) ($component['type'] ?? '')) !== 'HEADER') {
                continue;
            }
            if (strtoupper((string) ($component['format'] ?? '')) !== 'TEXT') {
                continue;
            }
            $text = (string) ($component['text'] ?? '');
            $max = 0;
            if (preg_match_all('/\{\{(\d+)\}\}/', $text, $m)) {
                foreach ($m[1] as $n) {
                    $max = max($max, (int) $n);
                }
            }

            return $max;
        }

        return 0;
    }

    /**
     * IMAGE / VIDEO / DOCUMENT header (often requires a media link on each send).
     */
    public static function headerMediaFormatFromComponents(?array $components): ?string
    {
        if (!$components) {
            return null;
        }
        foreach ($components as $component) {
            if (!is_array($component)) {
                continue;
            }
            if (strtoupper((string) ($component['type'] ?? '')) !== 'HEADER') {
                continue;
            }
            $fmt = strtoupper((string) ($component['format'] ?? ''));
            if (in_array($fmt, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                return $fmt;
            }
        }

        return null;
    }

    /**
     * Raw BODY component text for admin UI hints.
     */
    public static function bodyTextFromComponents(?array $components): string
    {
        if (!$components) {
            return '';
        }
        foreach ($components as $component) {
            if (!is_array($component)) {
                continue;
            }
            if (strtoupper((string) ($component['type'] ?? '')) === 'BODY') {
                return (string) ($component['text'] ?? '');
            }
        }

        return '';
    }

    /**
     * Meta templates use either positional {{1}}… or named {{first_name}}…. Named templates require parameter_name on send.
     *
     * @return array{format: string, named_param_names: array<int, string>, positional_count: int}
     */
    public static function resolveBodyParameterPlanFromTemplate(array $templateRow): array
    {
        $components = is_array($templateRow['components'] ?? null) ? $templateRow['components'] : [];
        $globalFormat = strtoupper((string) ($templateRow['parameter_format'] ?? ''));

        $bodyComp = null;
        foreach ($components as $c) {
            if (strtoupper((string) ($c['type'] ?? '')) === 'BODY') {
                $bodyComp = $c;
                break;
            }
        }
        if ($bodyComp === null) {
            return ['format' => 'positional', 'named_param_names' => [], 'positional_count' => 0];
        }

        $example = is_array($bodyComp['example'] ?? null) ? $bodyComp['example'] : [];
        if (!empty($example['body_text_named_params']) && is_array($example['body_text_named_params'])) {
            $names = [];
            foreach ($example['body_text_named_params'] as $row) {
                if (is_array($row) && isset($row['param_name'])) {
                    $names[] = (string) $row['param_name'];
                }
            }
            if ($names !== []) {
                return ['format' => 'named', 'named_param_names' => $names, 'positional_count' => count($names)];
            }
        }

        if ($globalFormat === 'NAMED') {
            $text = (string) ($bodyComp['text'] ?? '');
            if (preg_match_all('/\{\{([a-z][a-z0-9_]*)\}\}/', $text, $m)) {
                $names = array_values(array_unique($m[1]));

                return ['format' => 'named', 'named_param_names' => $names, 'positional_count' => count($names)];
            }
        }

        // Meta named-parameter templates often omit `parameter_format` on the stored row; HEADER already
        // infers names from {{snake_case}}. Do the same for BODY when there are no {{1}}-style slots.
        $bodyText = (string) ($bodyComp['text'] ?? '');
        if ($bodyText !== '' && !preg_match('/\{\{\d+\}\}/', $bodyText)) {
            if (preg_match_all('/\{\{([a-z][a-z0-9_]*)\}\}/', $bodyText, $m)) {
                $seen = [];
                $names = [];
                foreach ($m[1] as $name) {
                    if (!isset($seen[$name])) {
                        $seen[$name] = true;
                        $names[] = $name;
                    }
                }
                if ($names !== []) {
                    return ['format' => 'named', 'named_param_names' => $names, 'positional_count' => count($names)];
                }
            }
        }

        $n = self::countBodyPlaceholdersFromComponents($components);
        if ($n === 0 && isset($example['body_text'][0]) && is_array($example['body_text'][0])) {
            $n = count($example['body_text'][0]);
        }

        return ['format' => 'positional', 'named_param_names' => [], 'positional_count' => $n];
    }

    /**
     * @return array{format: string, named_param_names: array<int, string>, positional_count: int}
     */
    public static function resolveHeaderTextParameterPlanFromTemplate(array $templateRow): array
    {
        $components = is_array($templateRow['components'] ?? null) ? $templateRow['components'] : [];

        foreach ($components as $c) {
            if (!is_array($c)) {
                continue;
            }
            if (strtoupper((string) ($c['type'] ?? '')) !== 'HEADER') {
                continue;
            }
            if (strtoupper((string) ($c['format'] ?? '')) !== 'TEXT') {
                continue;
            }

            $example = is_array($c['example'] ?? null) ? $c['example'] : [];
            if (!empty($example['header_text_named_params']) && is_array($example['header_text_named_params'])) {
                $names = [];
                foreach ($example['header_text_named_params'] as $row) {
                    if (is_array($row) && isset($row['param_name'])) {
                        $names[] = (string) $row['param_name'];
                    }
                }
                if ($names !== []) {
                    return ['format' => 'named', 'named_param_names' => $names, 'positional_count' => count($names)];
                }
            }

            $text = (string) ($c['text'] ?? '');
            if (preg_match_all('/\{\{([a-z][a-z0-9_]*)\}\}/', $text, $m)) {
                $names = array_values(array_unique($m[1]));

                return ['format' => 'named', 'named_param_names' => $names, 'positional_count' => count($names)];
            }

            $max = 0;
            if (preg_match_all('/\{\{(\d+)\}\}/', $text, $m2)) {
                foreach ($m2[1] as $n) {
                    $max = max($max, (int) $n);
                }
            }

            return ['format' => 'positional', 'named_param_names' => [], 'positional_count' => $max];
        }

        return ['format' => 'positional', 'named_param_names' => [], 'positional_count' => 0];
    }

    /**
     * Reconstruct the message text as the customer sees it: substitute sent parameters into
     * template HEADER (text), BODY, then append FOOTER and BUTTON labels from the approved template row.
     *
     * @param  array<string, mixed>  $bodyPlan  From {@see resolveBodyParameterPlanFromTemplate()}
     * @param  array<string, mixed>  $headerTextPlan  From {@see resolveHeaderTextParameterPlanFromTemplate()}
     */
    public static function renderTemplateMessageAsSeenByCustomer(
        array $templateRow,
        array $headerTextStrings,
        array $bodyStrings,
        ?string $headerMediaUrl,
        ?string $headerMediaFormat,
        array $bodyPlan,
        array $headerTextPlan
    ): string {
        $components = is_array($templateRow['components'] ?? null) ? $templateRow['components'] : [];
        $lines = [];

        foreach ($components as $c) {
            if (!is_array($c) || strtoupper((string) ($c['type'] ?? '')) !== 'HEADER') {
                continue;
            }
            $fmt = strtoupper((string) ($c['format'] ?? 'TEXT'));
            if ($fmt === 'TEXT') {
                $raw = (string) ($c['text'] ?? '');
                $filled = self::substituteTemplateComponentPlaceholders($raw, $headerTextPlan, $headerTextStrings);
                if (trim($filled) !== '') {
                    $lines[] = $filled;
                }
            } elseif (in_array($fmt, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                $label = $headerMediaFormat !== null && $headerMediaFormat !== '' ? $headerMediaFormat : $fmt;
                if ($headerMediaUrl !== null && trim($headerMediaUrl) !== '') {
                    $lines[] = '['.$label.'] '.trim($headerMediaUrl);
                } else {
                    $lines[] = '['.$label.']';
                }
            }
            break;
        }

        foreach ($components as $c) {
            if (!is_array($c) || strtoupper((string) ($c['type'] ?? '')) !== 'BODY') {
                continue;
            }
            $raw = (string) ($c['text'] ?? '');
            $filled = self::substituteTemplateComponentPlaceholders($raw, $bodyPlan, $bodyStrings);
            if (trim($filled) !== '') {
                $lines[] = $filled;
            }
            break;
        }

        foreach ($components as $c) {
            if (!is_array($c) || strtoupper((string) ($c['type'] ?? '')) !== 'FOOTER') {
                continue;
            }
            $ft = trim((string) ($c['text'] ?? ''));
            if ($ft !== '') {
                $lines[] = $ft;
            }
            break;
        }

        foreach ($components as $c) {
            if (!is_array($c) || strtoupper((string) ($c['type'] ?? '')) !== 'BUTTONS') {
                continue;
            }
            $buttons = $c['buttons'] ?? [];
            if (!is_array($buttons)) {
                break;
            }
            foreach ($buttons as $b) {
                if (!is_array($b)) {
                    continue;
                }
                $bt = strtoupper((string) ($b['type'] ?? ''));
                $tx = trim((string) ($b['text'] ?? ''));
                if ($tx === '') {
                    continue;
                }
                if ($bt === 'URL') {
                    $url = trim((string) ($b['url'] ?? ''));
                    $lines[] = $url !== '' ? $tx."\n".$url : $tx;
                } elseif ($bt === 'PHONE_NUMBER') {
                    $pn = trim((string) ($b['phone_number'] ?? ''));
                    $lines[] = $pn !== '' ? $tx."\n".$pn : $tx;
                } else {
                    $lines[] = $tx;
                }
            }
            break;
        }

        return implode("\n\n", array_filter($lines, static fn ($x) => trim((string) $x) !== ''));
    }

    /**
     * @param  array{format?: string, named_param_names?: array<int, string>}  $plan
     * @param  array<int, string>  $values
     */
    private static function substituteTemplateComponentPlaceholders(string $raw, array $plan, array $values): string
    {
        $format = (string) ($plan['format'] ?? 'positional');
        $s = $raw;
        if ($format === 'named' && !empty($plan['named_param_names']) && is_array($plan['named_param_names'])) {
            foreach ($plan['named_param_names'] as $i => $name) {
                $val = (string) ($values[$i] ?? '');
                $s = str_replace('{{'.$name.'}}', $val, $s);
            }
        } else {
            foreach (array_values($values) as $idx => $val) {
                $s = str_replace('{{'.($idx + 1).'}}', (string) $val, $s);
            }
        }

        $s = preg_replace('/\{\{\d+\}\}/u', '', $s) ?? $s;
        $s = preg_replace('/\{\{[a-z][a-z0-9_]*\}\}/u', '', $s) ?? $s;

        return $s;
    }

    public static function previewTextFromComponents(?array $components): string
    {
        $state = self::extractTemplatePreviewState($components);
        $parts = [];
        if ($state['header']) {
            $h = $state['header'];
            $fmt = strtoupper((string) ($h['format'] ?? 'TEXT'));
            if ($fmt === 'TEXT' && ($h['display_text'] ?? '') !== '') {
                $parts[] = (string) $h['display_text'];
            } elseif (in_array($fmt, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                $parts[] = '[' . $fmt . ']';
            }
        }
        if (($state['body'] ?? '') !== '') {
            $parts[] = (string) $state['body'];
        }
        if (($state['footer'] ?? '') !== '') {
            $parts[] = (string) $state['footer'];
        }

        return implode("\n", array_filter($parts));
    }

    /**
     * Normalize synced/created template components into UI-friendly preview data.
     *
     * @return array{
     *   header: ?array{format: string, display_text: string, media_url: ?string},
     *   body: string,
     *   body_display: string,
     *   footer: string,
     *   buttons: array<int, array{type: string, text: string, url: string, phone_number: string}>
     * }
     */
    public static function extractTemplatePreviewState(?array $components): array
    {
        $out = [
            'header' => null,
            'body' => '',
            'body_display' => '',
            'footer' => '',
            'buttons' => [],
        ];

        if (!$components) {
            return $out;
        }

        foreach ($components as $c) {
            if (!is_array($c)) {
                continue;
            }
            $type = strtoupper((string) ($c['type'] ?? ''));
            $example = is_array($c['example'] ?? null) ? $c['example'] : [];

            if ($type === 'HEADER') {
                $format = strtoupper((string) ($c['format'] ?? 'TEXT'));
                $mediaUrl = null;
                $handles = $example['header_handle'] ?? null;
                if (is_array($handles)) {
                    foreach ($handles as $h) {
                        if (is_string($h) && filter_var($h, FILTER_VALIDATE_URL)) {
                            $mediaUrl = $h;

                            break;
                        }
                    }
                }
                $text = (string) ($c['text'] ?? '');
                $displayText = $text;
                $ht = $example['header_text'] ?? null;
                if (is_array($ht) && isset($ht[0])) {
                    $displayText = str_replace('{{1}}', (string) $ht[0], $displayText);
                } else {
                    $displayText = self::stripOrEllipsizePlaceholders($displayText);
                }
                $out['header'] = [
                    'format' => $format,
                    'display_text' => $displayText,
                    'media_url' => $mediaUrl,
                ];
            }

            if ($type === 'BODY') {
                $out['body'] = (string) ($c['text'] ?? '');
                $bt = $example['body_text'] ?? null;
                if (is_array($bt) && $bt !== []) {
                    $out['body_display'] = self::applyBodyTextExamples($out['body'], $bt);
                } else {
                    $out['body_display'] = self::stripOrEllipsizePlaceholders($out['body']);
                }
            }

            if ($type === 'FOOTER') {
                $out['footer'] = (string) ($c['text'] ?? '');
            }

            if ($type === 'BUTTONS') {
                $buttons = $c['buttons'] ?? [];
                if (is_array($buttons)) {
                    foreach ($buttons as $b) {
                        if (!is_array($b)) {
                            continue;
                        }
                        $out['buttons'][] = [
                            'type' => strtoupper((string) ($b['type'] ?? '')),
                            'text' => (string) ($b['text'] ?? ''),
                            'url' => (string) ($b['url'] ?? ''),
                            'phone_number' => (string) ($b['phone_number'] ?? ''),
                        ];
                    }
                }
            }
        }

        if ($out['body_display'] === '' && $out['body'] !== '') {
            $out['body_display'] = self::stripOrEllipsizePlaceholders($out['body']);
        }

        return $out;
    }

    private static function applyBodyTextExamples(string $body, array $examples): string
    {
        $s = $body;
        foreach (array_values($examples) as $idx => $replacement) {
            $s = str_replace('{{' . ($idx + 1) . '}}', (string) $replacement, $s);
        }

        return self::stripOrEllipsizePlaceholders($s);
    }

    private static function stripOrEllipsizePlaceholders(string $text): string
    {
        return preg_replace('/\{\{\d+\}\}/u', '…', $text) ?? $text;
    }

    /**
     * Map an uploaded file to Graph Resumable Upload file_type for template sample media.
     * Allowed: image/jpeg, image/png, video/mp4 (Meta upload guide).
     */
    public static function mapUploadedFileToGraphTemplateFileType(UploadedFile $file, string $format): ?string
    {
        $format = strtoupper($format);
        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());

        if ($format === 'IMAGE') {
            if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
                return $mime === 'image/png' ? 'image/png' : 'image/jpeg';
            }
            if (in_array($ext, ['jpg', 'jpeg'], true)) {
                return 'image/jpeg';
            }
            if ($ext === 'png') {
                return 'image/png';
            }
        }

        if ($format === 'VIDEO') {
            if ($mime === 'video/mp4' || $ext === 'mp4') {
                return 'video/mp4';
            }
        }

        return null;
    }

    /**
     * Upload a local file using Graph API Resumable Upload; returns handle "h" for template HEADER example.header_handle.
     *
     * @see https://developers.facebook.com/docs/graph-api/guides/upload
     */
    public function resumableUploadFileForTemplateSample(
        string $absolutePath,
        string $graphFileType,
        ?string &$error = null,
        ?array &$graphContext = null
    ): ?string {
        $graphContext = null;
        $error = null;
        $token = (string) config('services.whatsapp_cloud.token');
        $appId = (string) config('services.whatsapp_cloud.app_id');
        if ($token === '' || $appId === '') {
            $error = 'missing_token_or_app_id';

            return null;
        }

        if (!is_readable($absolutePath)) {
            $error = 'file_not_readable';

            return null;
        }

        $fileLength = filesize($absolutePath);
        if ($fileLength === false || $fileLength < 1) {
            $error = 'invalid_file_size';

            return null;
        }

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');
        $fileName = basename($absolutePath);
        $step1Url = sprintf('https://graph.facebook.com/%s/%s/uploads', $version, $appId);

        try {
            $r1 = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->post($step1Url, [
                    'file_name' => $fileName,
                    'file_length' => $fileLength,
                    'file_type' => $graphFileType,
                ]);

            $j1 = $r1->json();
            if ($r1->failed()) {
                $query = http_build_query([
                    'file_name' => $fileName,
                    'file_length' => (string) $fileLength,
                    'file_type' => $graphFileType,
                ], '', '&', PHP_QUERY_RFC3986);
                $r1 = Http::withToken($token)
                    ->acceptJson()
                    ->post($step1Url . '?' . $query);
                $j1 = $r1->json();
            }

            if ($r1->failed()) {
                $error = 'upload_session_http_' . $r1->status() . ':' . $r1->body();
                $graphContext = ['step' => 1, 'graph_response' => $j1 ?? $r1->body()];
                Log::warning('WhatsApp resumable upload step1 failed', ['detail' => $error]);

                return null;
            }

            $sessionId = is_array($j1) ? ($j1['id'] ?? null) : null;
            if (!is_string($sessionId) || $sessionId === '') {
                $error = 'upload_session_missing_id';
                $graphContext = ['step' => 1, 'graph_response' => $j1];

                return null;
            }

            $binary = file_get_contents($absolutePath);
            if ($binary === false) {
                $error = 'file_read_failed';

                return null;
            }

            $step2Url = sprintf(
                'https://graph.facebook.com/%s/%s',
                $version,
                rawurlencode($sessionId)
            );

            $r2 = Http::withToken($token)
                ->acceptJson()
                ->withHeaders(['file_offset' => '0'])
                ->withBody($binary, 'application/octet-stream')
                ->post($step2Url);

            $j2 = $r2->json();
            if ($r2->failed()) {
                $error = 'upload_binary_http_' . $r2->status() . ':' . $r2->body();
                $graphContext = ['step' => 2, 'graph_response' => $j2 ?? $r2->body()];
                Log::warning('WhatsApp resumable upload step2 failed', ['detail' => $error]);

                return null;
            }

            if (!is_array($j2)) {
                $error = 'upload_handle_invalid_response';

                return null;
            }

            $handle = $j2['h'] ?? null;
            if (!is_string($handle) || $handle === '') {
                $error = 'upload_handle_missing';
                $graphContext = ['step' => 2, 'graph_response' => $j2];

                return null;
            }

            $graphContext = ['step' => 2, 'handle_prefix' => mb_substr($handle, 0, 24) . '…'];

            return $handle;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::warning('WhatsApp resumable upload exception', ['error' => $error]);

            return null;
        }
    }

    /**
     * Submit template components to Meta (create / update request — goes to review).
     *
     * @param  array<int, array<string, mixed>>  $components
     * @return array<string, mixed>|null
     */
    public function submitMessageTemplateForWaba(
        string $name,
        string $languageCode,
        string $category,
        array $components,
        ?string &$error = null,
        ?array &$graphContext = null
    ): ?array {
        $graphContext = null;
        $error = null;
        $token = (string) config('services.whatsapp_cloud.token');
        $wabaId = (string) config('services.whatsapp_cloud.waba_id');
        if ($token === '' || $wabaId === '') {
            $error = 'missing_waba_or_token';

            return null;
        }

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');
        $url = "https://graph.facebook.com/{$version}/{$wabaId}/message_templates";

        $payload = [
            'name' => $name,
            'language' => $languageCode,
            'category' => strtoupper($category),
            'components' => array_values($components),
        ];

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            $respPayload = $response->json();
            $httpStatus = $response->status();

            if ($response->failed()) {
                $error = 'http_' . $httpStatus . ':' . $response->body();
                Log::warning('WhatsApp create template failed', [
                    'name' => $name,
                    'http_status' => $httpStatus,
                    'graph_response' => $respPayload ?? $response->body(),
                ]);
                $graphContext = [
                    'http_status' => $httpStatus,
                    'graph_response' => $respPayload,
                ];

                return null;
            }

            if (!is_array($respPayload)) {
                $error = 'invalid_graph_response';

                return null;
            }

            $graphContext = ['http_status' => $httpStatus, 'graph_response' => $respPayload];

            return $respPayload;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::warning('WhatsApp create template exception', ['error' => $error]);

            return null;
        }
    }

    /**
     * Create a new message template on the WhatsApp Business Account (submitted for Meta review).
     * Template name must be lowercase letters, numbers, and underscores only.
     *
     * @return array<string, mixed>|null  Graph JSON payload on success (includes id, status, category, …)
     */
    public function createMessageTemplate(
        string $name,
        string $languageCode,
        string $category,
        string $bodyText,
        ?string $headerText,
        ?string &$error = null,
        ?array &$graphContext = null
    ): ?array {
        $components = [];
        if ($headerText !== null && trim($headerText) !== '') {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => trim($headerText),
            ];
        }
        $components[] = [
            'type' => 'BODY',
            'text' => $bodyText,
        ];

        return $this->submitMessageTemplateForWaba($name, $languageCode, $category, $components, $error, $graphContext);
    }

    /**
     * Normalize a template name to Meta rules: lowercase [a-z0-9_].
     */
    public static function normalizeTemplateName(string $raw): string
    {
        $s = strtolower(trim($raw));
        $s = preg_replace('/[^a-z0-9_]+/', '_', $s) ?? '';
        $s = trim((string) $s, '_');

        return $s;
    }
}
