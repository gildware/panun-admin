<?php

namespace Modules\AdminModule\Support;

class ProcessGuideText
{
    /**
     * Escape HTML and render **bold** markers as styled emphasis.
     */
    public static function format(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $escaped = e($text);

        return (string) preg_replace(
            '/\*\*(.+?)\*\*/',
            '<strong class="pg-txt-em">$1</strong>',
            $escaped,
        );
    }
}
