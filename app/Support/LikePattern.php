<?php

namespace App\Support;

/**
 * Builds LIKE patterns from user input so %, _ and \ are matched literally.
 */
class LikePattern
{
    public static function escape(string $value): string
    {
        return str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $value);
    }

    public static function contains(string $value): string
    {
        return '%' . self::escape($value) . '%';
    }
}
