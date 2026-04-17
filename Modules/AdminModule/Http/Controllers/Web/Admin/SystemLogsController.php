<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SystemLogsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $level = strtoupper((string) $request->query('level', 'ERROR'));
        $q = trim((string) $request->query('q', ''));
        $lines = (int) $request->query('lines', 500);
        $lines = max(50, min(5000, $lines));

        $path = storage_path('logs/laravel.log');
        $exists = is_file($path) && is_readable($path);

        $rawLines = $exists ? $this->tailLines($path, $lines) : [];

        $filtered = array_values(array_filter($rawLines, function (string $line) use ($level, $q) {
            if ($level !== 'ALL') {
                // Typical laravel log: "[date] env.LEVEL: message"
                if (! preg_match('/\.\s*' . preg_quote($level, '/') . '\s*:/i', $line)) {
                    return false;
                }
            }
            if ($q !== '' && stripos($line, $q) === false) {
                return false;
            }
            return true;
        }));

        return view('adminmodule::admin.system-logs.index', [
            'logFilePath' => $path,
            'logFileExists' => $exists,
            'level' => $level,
            'q' => $q,
            'lines' => $lines,
            'items' => array_reverse($filtered), // newest first (tail returns oldest->newest)
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        $this->authorizeAccess();

        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            Toastr::error(translate('Log_file_not_found_or_not_readable'));
            return redirect()->route('admin.system-logs.index');
        }

        try {
            // Truncate the file safely (preserves file permissions/ownership).
            $fp = fopen($path, 'c');
            if ($fp === false) {
                Toastr::error(translate('Log_file_not_found_or_not_readable'));
                return redirect()->route('admin.system-logs.index');
            }

            if (flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);

            Toastr::success(translate('Logs_cleared_successfully'));
        } catch (\Throwable $e) {
            Toastr::error(translate('Something_went_wrong'));
        }

        return redirect()->route('admin.system-logs.index');
    }

    private function authorizeAccess(): void
    {
        if (! Gate::allows('business_view') && ! Gate::allows('configuration_view') && ! Gate::allows('backup_view')) {
            abort(403);
        }
    }

    /**
     * Read the last N lines of a file without loading full file.
     *
     * @return array<int, string>
     */
    private function tailLines(string $filePath, int $maxLines): array
    {
        $fp = fopen($filePath, 'rb');
        if ($fp === false) {
            return [];
        }

        $buffer = '';
        $chunkSize = 8192;
        $pos = -1;
        $lineCount = 0;

        fseek($fp, 0, SEEK_END);
        $fileSize = ftell($fp);
        if ($fileSize === false || $fileSize === 0) {
            fclose($fp);
            return [];
        }

        while ($lineCount <= $maxLines && -$pos < $fileSize) {
            $seek = max(-$fileSize, $pos - $chunkSize + 1);
            $readSize = $pos - $seek + 1;

            fseek($fp, $seek, SEEK_END);
            $chunk = fread($fp, $readSize);
            if ($chunk === false) {
                break;
            }

            $buffer = $chunk . $buffer;
            $lineCount = substr_count($buffer, "\n");
            $pos = $seek - 1;
        }

        fclose($fp);

        $lines = preg_split("/\r\n|\n|\r/", $buffer) ?: [];
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, -$maxLines);
        }

        // Drop possible trailing empty line from split
        if (count($lines) && trim((string) end($lines)) === '') {
            array_pop($lines);
        }

        return $lines;
    }
}

