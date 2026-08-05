<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidVoiceRecording implements ValidationRule
{
    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = ['mp3', 'wav', 'webm', 'ogg', 'm4a', 'aac'];

    /** @var list<string> */
    private const ALLOWED_MIMES = [
        'audio/mpeg',
        'audio/mp3',
        'audio/x-mpeg',
        'audio/mpeg3',
        'audio/x-mp3',
        'audio/wav',
        'audio/x-wav',
        'audio/webm',
        'audio/ogg',
        'audio/mp4',
        'audio/x-m4a',
        'audio/aac',
        'audio/x-aac',
        'audio/adts',
        'audio/vnd.dlna.adts',
        'video/mp4',
    ];

    /** MIME types that often appear when finfo cannot identify the audio container. */
    /** @var list<string> */
    private const UNRELIABLE_MIMES = [
        'application/octet-stream',
        'binary/octet-stream',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail(translate('Please_upload_a_valid_audio_recording'));

            return;
        }

        $mime = strtolower((string) $value->getMimeType());
        $extension = strtolower((string) $value->getClientOriginalExtension());

        if (in_array($mime, self::ALLOWED_MIMES, true)) {
            return;
        }

        if (in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            if (in_array($mime, self::UNRELIABLE_MIMES, true) || str_starts_with($mime, 'audio/')) {
                return;
            }
        }

        $fail(translate('Please_upload_a_valid_audio_recording'));
    }
}
