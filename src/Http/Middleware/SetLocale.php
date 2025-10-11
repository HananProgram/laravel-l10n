<?php

namespace HananProgram\L10n\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $r, Closure $next)
    {
        $allowed = config('l10n.enabled_locales', ['en','ar']);
        $loc = $r->session()->get('locale', $r->cookie('locale', config('app.locale', 'en')));
        app()->setLocale(in_array($loc, $allowed, true) ? $loc : 'en');
        return $next($r);
    }
}
