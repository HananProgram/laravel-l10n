<?php
use HananProgram\L10n\Support\Trans;

if (!function_exists('tr')) {
    function tr(string $english, string $group = 'ui'): string {
        return Trans::t($english, $group);
    }
}
if (!function_exists('trk')) {
    function trk(string $key, string $english, string $group = 'ui'): string {
        return Trans::tKey($key, $english, $group);
    }
}
