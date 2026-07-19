<?php

namespace Buildr\Support;

/**
 * Rich-ish text rendering: plain text becomes clean paragraphs (blank line =
 * new <p>, single newline = <br>); anything already containing HTML tags is
 * rendered verbatim so power users can hand-write markup.
 */
final class Richtext
{
    public static function render(?string $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        if (preg_match('/<[a-z][^>]*>/i', $value)) {
            return $value; // already HTML
        }

        $paragraphs = preg_split('/\R{2,}/', trim($value));

        return implode('', array_map(
            fn ($p) => '<p>'.nl2br(e($p), false).'</p>',
            $paragraphs
        ));
    }
}
