<?php

namespace HananProgram\L10n\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController
{
    public function switch(string $loc, Request $request)
    {
        $allowed = config('l10n.enabled_locales', ['en','ar']);
        abort_unless(in_array($loc, $allowed, true), 404);

        session(['locale' => $loc]);
        session()->save();
        app()->setLocale($loc);

        return back()->withCookie(cookie('locale', $loc, 60*24*365));
    }
}
