<?php

namespace Modules\AdminModule\Support;

class ProcessGuideSearch
{
    /** @var list<string> */
    private const SKIP_KEYS = [
        'type',
        'icon',
        'flowchart',
        'flowcharts',
        'correct',
        'image',
        'url',
        'ref',
        'key',
    ];

    /**
     * @return list<array{
     *     guideKey: string,
     *     guideTitle: string,
     *     slideId: string,
     *     slideIndex: int,
     *     slideNumber: int,
     *     slideTitle: string,
     *     text: string,
     *     snippet: string,
     * }>
     */
    public static function index(): array
    {
        $entries = [];

        foreach (ProcessGuideRegistry::all() as $guideKey => $guide) {
            $trainingClass = $guide['training_guide'] ?? null;
            if (! is_string($trainingClass) || ! method_exists($trainingClass, 'slides')) {
                continue;
            }

            foreach ($trainingClass::slides() as $index => $slide) {
                if (! is_array($slide)) {
                    continue;
                }

                $text = self::collectText($slide);
                $slideTitle = (string) ($slide['title'] ?? '');

                $entries[] = [
                    'guideKey' => (string) $guideKey,
                    'guideTitle' => (string) ($guide['title'] ?? ''),
                    'slideId' => (string) ($slide['id'] ?? ''),
                    'slideIndex' => (int) $index,
                    'slideNumber' => (int) ($slide['number'] ?? ($index + 1)),
                    'slideTitle' => $slideTitle,
                    'text' => $text,
                    'snippet' => self::snippet($slide, $text),
                ];
            }
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $slide
     */
    private static function snippet(array $slide, string $text): string
    {
        foreach (['overview', 'subtitle', 'tagline', 'intro', 'note'] as $field) {
            if (! empty($slide[$field]) && is_string($slide[$field])) {
                return self::truncate(self::normalize($slide[$field]));
            }
        }

        return self::truncate($text);
    }

    /**
     * @param  mixed  $data
     */
    private static function collectText($data): string
    {
        if (is_string($data)) {
            return self::normalize($data);
        }

        if (! is_array($data)) {
            return '';
        }

        $parts = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array($key, self::SKIP_KEYS, true)) {
                continue;
            }

            $part = self::collectText($value);
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        return self::normalize(implode(' ', $parts));
    }

    private static function normalize(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace('**', '', $text);

        return (string) preg_replace('/\s+/u', ' ', trim($text));
    }

    private static function truncate(string $text, int $limit = 160): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 1)).'…';
    }
}
