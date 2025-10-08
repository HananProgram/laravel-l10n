<?php

namespace HananProgram\L10n\Services;

use Stichoza\GoogleTranslate\GoogleTranslate;

class AutoTranslator
{
    public static function translate(?string $text, string $to, string $from = 'auto'): ?string
    {
        $text = trim((string) $text);
        if ($text === '') return null;

        try {
            $tr = new GoogleTranslate($to);
            if ($from !== 'auto') $tr->setSource($from);
            return $tr->translate($text);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
