<?php

namespace HananProgram\L10n;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use HananProgram\L10n\Support\Trans;

class L10nServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/l10n.php', 'l10n');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/l10n.php' => config_path('l10n.php')], 'l10n-config');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'l10n');
        $this->loadRoutesFrom(__DIR__.'/../routes/l10n.php');

        Blade::directive('tr', fn($e) => "<?php echo e(\\HananProgram\\L10n\\Support\\Trans::t($e)); ?>");
        Blade::directive('trk', fn($e) => "<?php echo e(\\HananProgram\\L10n\\Support\\Trans::tKey(...[$e])); ?>");

        // توافق مع كودك الحالي إن وُجد
        if (!class_exists('\\App\\Support\\Trans')) {
            class_alias(Trans::class, '\\App\\Support\\Trans');
        }
        if (!function_exists('tr')) {
            function tr(string $english, string $group = 'ui'): string { return Trans::t($english, $group); }
        }
        if (!function_exists('trk')) {
            function trk(string $key, string $english, string $group = 'ui'): string { return Trans::tKey($key, $english, $group); }
        }
    }
}
