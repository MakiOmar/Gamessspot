<?php

namespace App\Support;

class HtmlSanitizer
{
    /**
     * Sanitize WYSIWYG HTML for catalog descriptions.
     */
    public static function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $trimmed = trim($html);
        $trimmed = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $trimmed) ?? $trimmed;
        $emptyEditorValues = ['', '<p><br></p>', '<p></p>', '<p><br/></p>'];
        if (in_array(strtolower($trimmed), $emptyEditorValues, true)) {
            return null;
        }

        $clean = strip_tags($trimmed, '<p><br><b><strong><i><em><u><ul><ol><li><a><h2><h3><h4><blockquote><span><div><hr>');
        $clean = preg_replace('/\s*on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
        $clean = preg_replace('/href\s*=\s*(["\'])\s*javascript:[^"\']*\1/i', 'href="#"', $clean) ?? $clean;
        $clean = trim($clean);

        return $clean === '' ? null : $clean;
    }
}
