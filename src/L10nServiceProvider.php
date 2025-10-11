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
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // ✅ ميدلوير اللغة يضاف تلقائيًا لكل مشروع
        $this->app['router']->pushMiddlewareToGroup('web', \HananProgram\L10n\Http\Middleware\SetLocale::class);

        \Illuminate\Support\Facades\Blade::directive('tr', fn($e) => "<?php echo e(\\HananProgram\\L10n\\Support\\Trans::t($e)); ?>");
        \Illuminate\Support\Facades\Blade::directive('trk', fn($e) => "<?php echo e(\\HananProgram\\L10n\\Support\\Trans::tKey(...[$e])); ?>");

        // ✅ ديركتيات لترويس الـ HTML بدون تعديل يدوي متكرر
        \Illuminate\Support\Facades\Blade::directive('htmlLang', fn() => "<?= app()->getLocale(); ?>");
        \Illuminate\Support\Facades\Blade::directive('htmlDir',  fn() => "<?= app()->isLocale('ar') ? 'rtl' : 'ltr'; ?>");
    }

}
