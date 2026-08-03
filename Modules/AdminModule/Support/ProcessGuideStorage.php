<?php

namespace Modules\AdminModule\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

class ProcessGuideStorage
{
    public const SLUG = 'lead-qualification';

    public static function directory(): string
    {
        return storage_path('app/process-guide/'.self::SLUG);
    }

    public static function boardPath(): string
    {
        return self::directory().'/board.json';
    }

    public static function groupsPath(): string
    {
        return self::directory().'/groups.json';
    }

    public static function defaultBoardPath(): string
    {
        return public_path('assets/admin-module/process-guide/miro-board.json');
    }

    /** @return array<string, mixed> */
    public static function loadBoard(): array
    {
        $path = File::exists(self::boardPath()) ? self::boardPath() : self::defaultBoardPath();
        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            throw new RuntimeException('Process guide board JSON is invalid.');
        }

        return $data;
    }

    /** @return array<int, array<string, mixed>> */
    public static function groups(): array
    {
        if (File::exists(self::groupsPath())) {
            $data = json_decode(File::get(self::groupsPath()), true);

            return is_array($data) ? $data : ProcessGuideGroups::definitions();
        }

        return ProcessGuideGroups::definitions();
    }

    /** @param array<string, mixed> $board */
    public static function saveBoard(array $board): void
    {
        self::ensureDirectory();
        $board = self::normalizeBoard($board);
        File::put(self::boardPath(), json_encode($board, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        File::put(self::defaultBoardPath(), json_encode($board, JSON_UNESCAPED_UNICODE));
    }

    /** @param array<int, array<string, mixed>> $groups */
    public static function saveGroups(array $groups): void
    {
        self::ensureDirectory();
        File::put(self::groupsPath(), json_encode(array_values($groups), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private static function ensureDirectory(): void
    {
        if (! File::isDirectory(self::directory())) {
            File::makeDirectory(self::directory(), 0755, true);
        }
    }

    /** @param array<string, mixed> $board */
    private static function normalizeBoard(array $board): array
    {
        $board['shapes'] = array_values($board['shapes'] ?? []);
        $board['connectors'] = array_values($board['connectors'] ?? []);
        $board['labels'] = array_values($board['labels'] ?? []);

        $xs = [];
        $ys = [];
        foreach ($board['shapes'] as $shape) {
            $x = (float) ($shape['x'] ?? 0);
            $y = (float) ($shape['y'] ?? 0);
            $w = (float) ($shape['w'] ?? 0);
            $h = (float) ($shape['h'] ?? 0);
            $xs[] = $x - $w / 2;
            $xs[] = $x + $w / 2;
            $ys[] = $y - $h / 2;
            $ys[] = $y + $h / 2;
        }
        foreach ($board['labels'] as $label) {
            $xs[] = (float) ($label['x'] ?? 0);
            $ys[] = (float) ($label['y'] ?? 0);
        }

        if ($xs !== [] && $ys !== []) {
            $pad = 500;
            $board['bounds'] = [
                'minX' => min($xs) - $pad,
                'minY' => min($ys) - $pad,
                'maxX' => max($xs) + $pad,
                'maxY' => max($ys) + $pad,
            ];
        }

        return $board;
    }
}
