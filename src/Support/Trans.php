<?php

namespace HananProgram\L10n\Support;

use Illuminate\Support\Str;
use Spatie\TranslationLoader\LanguageLine;
use HananProgram\L10n\Services\AutoTranslator;

class Trans
{
    public static function t(string $english, string $group = null): string
    {
        $group ??= config('l10n.default_group', 'ui');
        $key = Str::slug($english, '_');
        return static::tKey($key, $english, $group);
    }

    public static function tKey(string $key, string $english, string $group = null): string
    {
        $group ??= config('l10n.default_group', 'ui');

        $line = LanguageLine::firstOrCreate(
            ['group' => $group, 'key' => $key],
            ['text' => ['en' => $english]]
        );

        $text = $line->text ?? [];

        $syncSourceEn = (bool) config('l10n.sync_source_en', false);

        $enInDb = (string) ($text['en'] ?? '');
        $english = (string) $english;

        if ($english !== '') {
            if ($enInDb === '') {
                $text['en'] = $english;
                $line->update(['text' => $text]);
                cache()->forget("spatie.translation-loader.{$group}");
            } elseif ($syncSourceEn && $enInDb !== $english) {
                $text['en'] = $english;
                $line->update(['text' => $text]);
                cache()->forget("spatie.translation-loader.{$group}");
            }
        }

        if (empty($text['ar']) && config('l10n.auto_translate')) {
            if ($auto = AutoTranslator::translate($text['en'], 'ar', 'en')) {
                $text['ar'] = $auto;
                $line->update(['text' => $text]);
                cache()->forget("spatie.translation-loader.{$group}");
            }
        }

        $locale = app()->getLocale() ?: 'en';
        return $text[$locale] ?? ($text['en'] ?? $english);
    }
}
