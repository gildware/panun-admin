<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Audio playback that honors HTTP Range requests.
 *
 * The browser timeline sends Range: bytes=… when you skip forward or back.
 * A normal full-file stream ignores that, so the playhead snaps back to the start.
 */
final class SeekableRecordingResponse
{
    public static function fromDisk(string $diskName, string $path, ?string $mime = null, ?string $filename = null): BinaryFileResponse
    {
        $disk = Storage::disk($diskName);
        abort_unless($disk->exists($path), 404);

        $local = self::localAbsolutePath($disk, $path);
        $file = $local ?? self::cacheRemoteDiskFile($diskName, $path);

        return self::file($file, self::mime($mime, $file), $filename);
    }

    public static function fromUpstreamUrl(string $url, ?string $mime = null): BinaryFileResponse
    {
        $file = self::cacheUrl($url, 'upstream|'.$url);

        return self::file($file, self::mime($mime, $file), null);
    }

    private static function file(string $absolutePath, string $mime, ?string $filename): BinaryFileResponse
    {
        $response = new BinaryFileResponse($absolutePath);
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Cache-Control', 'private, max-age=3600');
        $response->setAutoLastModified();

        $downloadName = trim((string) $filename);
        if ($downloadName !== '') {
            $response->setContentDisposition('inline', $downloadName);
        }

        return $response;
    }

    private static function localAbsolutePath(object $disk, string $path): ?string
    {
        if (! method_exists($disk, 'path')) {
            return null;
        }

        try {
            $absolute = $disk->path($path);
        } catch (\Throwable) {
            return null;
        }

        return is_string($absolute) && is_file($absolute) ? $absolute : null;
    }

    private static function cacheRemoteDiskFile(string $diskName, string $path): string
    {
        $disk = Storage::disk($diskName);
        $target = self::cachePath($diskName.'|'.$path);
        if (self::fresh($target)) {
            return $target;
        }

        $url = null;
        try {
            $url = $disk->url($path);
        } catch (\Throwable) {
            $url = null;
        }

        if (is_string($url) && $url !== '' && self::download($url, $target)) {
            return $target;
        }

        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            abort(404);
        }

        $tmp = $target.'.'.getmypid().'.tmp';
        $out = fopen($tmp, 'wb');
        if ($out === false) {
            fclose($stream);
            abort(404);
        }

        try {
            stream_copy_to_stream($stream, $out);
        } finally {
            fclose($out);
            fclose($stream);
        }

        if (! is_file($tmp) || filesize($tmp) === 0) {
            @unlink($tmp);
            abort(404);
        }

        rename($tmp, $target);

        return $target;
    }

    private static function cacheUrl(string $url, string $key): string
    {
        $target = self::cachePath($key);
        if (self::fresh($target) || self::download($url, $target)) {
            return $target;
        }

        abort(404);
    }

    private static function download(string $url, string $target): bool
    {
        if (self::fresh($target)) {
            return true;
        }

        $tmp = $target.'.'.getmypid().'.tmp';
        try {
            $response = Http::timeout(90)->sink($tmp)->get($url);
        } catch (\Throwable) {
            @unlink($tmp);

            return false;
        }

        if (! $response->successful() || ! is_file($tmp) || filesize($tmp) === 0) {
            @unlink($tmp);

            return false;
        }

        rename($tmp, $target);

        return true;
    }

    private static function cachePath(string $key): string
    {
        $dir = storage_path('app/recording-playback-cache');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach (glob($dir.'/*') ?: [] as $old) {
            if (is_file($old) && filemtime($old) < time() - 86400) {
                @unlink($old);
            }
        }

        return $dir.'/'.sha1($key);
    }

    private static function fresh(string $path): bool
    {
        return is_file($path) && filesize($path) > 0 && filemtime($path) > time() - 21600;
    }

    private static function mime(?string $mime, string $file): string
    {
        $given = trim((string) $mime);
        if ($given !== '') {
            return $given;
        }

        $detected = mime_content_type($file);

        return is_string($detected) && $detected !== '' ? $detected : 'audio/mpeg';
    }
}
