<?php

namespace HananProgram\L10n\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\TranslationLoader\LanguageLine;
use HananProgram\L10n\Support\Trans;

class TranslateController
{
    public function index(Request $r)
    {
        $q = trim($r->get('q', ''));
        $group = $r->get('group', config('l10n.default_group', 'ui'));
        $perPage = (int) ($r->get('per', 20));
        $locale = app()->getLocale();

        foreach ([
            'Translations',
            'Reset',
            'Search',
            'Search key or text',
            'Current Translations',
            'Key',
            'Actions',
            'Save',
            'Save All',
            'All',
            'missing',
            'EN missing',
            'AR missing',
            'Saved successfully'
        ] as $seed) {
            Trans::t($seed, $group);
        }

        $items = LanguageLine::query()
            ->where('group', $group)
            ->when($q, function ($qq) use ($q) {
                $qq->where('key', 'like', "%$q%")
                    ->orWhere('text->en', 'like', "%$q%")
                    ->orWhere('text->ar', 'like', "%$q%");
            })
            ->orderBy('key')
            ->paginate($perPage)
            ->appends(compact('q', 'group', 'perPage'));

        $locales = config('l10n.enabled_locales', ['en', 'ar']);
        $groups = LanguageLine::select('group')->distinct()->pluck('group')->sort()->values();

        return view('l10n::translate.index', compact('items', 'q', 'group', 'locale', 'locales', 'groups', 'perPage'));
    }

    public function update(Request $r, int $id)
    {
        $data = $r->validate(['en' => ['nullable', 'string'], 'ar' => ['nullable', 'string']]);

        $line = LanguageLine::findOrFail($id);
        $t = $line->text;
        if ($r->has('en'))
            $t['en'] = $data['en'] ?? '';
        if ($r->has('ar'))
            $t['ar'] = $data['ar'] ?? '';
        $line->update(['text' => $t]);

        cache()->flush();
        return back()->with('ok', 'saved');
    }

    public function bulk(Request $r)
    {
        $data = $r->validate(['values_en' => ['array'], 'values_ar' => ['array']]);

        $ids = collect(array_keys($data['values_en'] ?? []))
            ->merge(array_keys($data['values_ar'] ?? []))
            ->unique();

        foreach ($ids as $id) {
            if (!$line = LanguageLine::find($id))
                continue;
            $t = $line->text;
            if (array_key_exists($id, $data['values_en'] ?? []))
                $t['en'] = $data['values_en'][$id] ?? '';
            if (array_key_exists($id, $data['values_ar'] ?? []))
                $t['ar'] = $data['values_ar'][$id] ?? '';
            $line->update(['text' => $t]);
        }

        cache()->flush();
        return back()->with([
            'message' => tr('Translations saved successfully!'),
            'type' => 'success',
        ]);
    }
}
