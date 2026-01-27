<?php

namespace HananProgram\L10n\Support;

use Illuminate\Support\Str;
use Spatie\TranslationLoader\LanguageLine;
use HananProgram\L10n\Services\AutoTranslator;

class Trans
{
    /**
     * @var array Static cache to store loaded translations for the current request.
     */
    protected static array $requestCache = [];

    /**
     * @var array Keeps track of which groups have been fully loaded from the database.
     */
    protected static array $groupsLoaded = [];

    public static function t(string $english, string $group = null): string
    {
        $group ??= config('l10n.default_group', 'ui');
        $key = Str::slug($english, '_');
        return static::tKey($key, $english, $group);
    }

    public static function tKey(string $key, string $english, string $group = null): string
    {
        $group ??= config('l10n.default_group', 'ui');
        $locale = app()->getLocale() ?: 'en';

        // 1. Check Request Cache First
        if (isset(static::$requestCache[$group][$key][$locale])) {
            return static::$requestCache[$group][$key][$locale];
        }

        // 2. Bulk Load Group if not loaded yet
        if (!isset(static::$groupsLoaded[$group])) {
            $lines = LanguageLine::where('group', $group)->get();
            foreach ($lines as $line) {
                static::$requestCache[$group][$line->key] = $line->text;
            }
            static::$groupsLoaded[$group] = true;

            // Re-check after bulk load
            if (isset(static::$requestCache[$group][$key][$locale])) {
                return static::$requestCache[$group][$key][$locale];
            }
        }

        // 3. Fallback to Database for single item (Discovery/Sync)
        // This only runs if the key wasn't in the initial bulk load or cache
        $line = LanguageLine::firstOrCreate(
            ['group' => $group, 'key' => $key],
            ['text' => ['en' => $english]]
        );

        $text = $line->text ?? [];
        $syncSourceEn = (bool) config('l10n.sync_source_en', false);
        $enInDb = (string) ($text['en'] ?? '');
        $english = (string) $english;
        $needsUpdate = false;

        if ($english !== '') {
            if ($enInDb === '') {
                $text['en'] = $english;
                $needsUpdate = true;
            } elseif ($syncSourceEn && $enInDb !== $english) {
                $text['en'] = $english;
                $needsUpdate = true;
            }
        }

        if (empty($text['ar']) && config('l10n.auto_translate')) {
            if ($auto = AutoTranslator::translate($text['en'], 'ar', 'en')) {
                $text['ar'] = $auto;
                $needsUpdate = true;
            }
        }

        if ($needsUpdate) {
            $line->update(['text' => $text]);
            cache()->forget("spatie.translation-loader.{$group}");
        }

        // Update Request Cache
        static::$requestCache[$group][$key] = $text;

        return $text[$locale] ?? ($text['en'] ?? $english);
    }
}
