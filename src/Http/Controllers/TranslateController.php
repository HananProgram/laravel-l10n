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
            'Saved successfully',
            'Export',
            'Import'
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

    public function export()
    {
        $group = request('group', config('l10n.default_group', 'ui'));
        $translations = LanguageLine::where('group', $group)->orderBy('key')->get();

        $data = $translations->map(function ($t) {
            return [
                'key' => $t->key,
                'en'  => $t->text['en'] ?? $t->key,
                'ar'  => $t->text['ar'] ?? '',
            ];
        })->toArray();

        $fileName = 'translations_'.$group.'_'.now()->format('Y-m-d_H-i-s').'.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function import(Request $r)
    {
        $r->validate([
            'import_file' => ['required', 'file', 'mimetypes:application/json,text/json,text/plain', 'max:10240'],
            'group'       => ['required', 'string'],
        ]);

        try {
            $content = file_get_contents($r->file('import_file')->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                return back()->with('error', tr('Invalid JSON file format'));
            }

            $group = $r->get('group');
            $imported = 0;
            $updated = 0;
            $skipped = 0;

            \DB::beginTransaction();
            foreach ($data as $item) {
                if (!isset($item['key']) || empty($item['key'])) {
                    $skipped++;
                    continue;
                }

                $text = [
                    'en' => $item['en'] ?? $item['key'] ?? '',
                    'ar' => $item['ar'] ?? '',
                ];

                $line = LanguageLine::where('group', $group)->where('key', $item['key'])->first();

                if ($line) {
                    $line->update(['text' => $text]);
                    $updated++;
                } else {
                    LanguageLine::create([
                        'group' => $group,
                        'key'   => $item['key'],
                        'text'  => $text,
                    ]);
                    $imported++;
                }
            }
            \DB::commit();

            cache()->flush();

            $msg = tr('Imported').": $imported, ".tr('Updated').": $updated";
            if ($skipped > 0) $msg .= ", ".tr('Skipped').": $skipped";

            return back()->with('ok', $msg);

        } catch (\Throwable $e) {
            \DB::rollBack();
            return back()->with('error', tr('Failed to import').': '.$e->getMessage());
        }
    }
}
