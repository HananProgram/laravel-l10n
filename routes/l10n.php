<?php

use Illuminate\Support\Facades\Route;
use HananProgram\L10n\Http\Controllers\LocaleController;
use HananProgram\L10n\Http\Controllers\TranslateController;

Route::middleware('web')->get('/locale/{loc}', [LocaleController::class, 'switch'])
    ->whereIn('loc', config('l10n.enabled_locales', ['en','ar']))
    ->name('l10n.locale.switch');

Route::middleware(config('l10n.admin_middleware', ['web','auth','App\Http\Middleware\superadminMiddleware']))
    ->prefix(config('l10n.admin_route_prefix', 'superadmin'))
    ->group(function () {
        Route::get('/translate', [TranslateController::class, 'index'])->name('l10n.translate.index');
        Route::post('/translate/update/{id}', [TranslateController::class, 'update'])->name('l10n.translate.update');
        Route::post('/translate/bulk', [TranslateController::class, 'bulk'])->name('l10n.translate.bulk');
        Route::get('/translate/export', [TranslateController::class, 'export'])->name('l10n.translate.export');
        Route::post('/translate/import', [TranslateController::class, 'import'])->name('l10n.translate.import');
    });
