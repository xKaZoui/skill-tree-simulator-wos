<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Controllers\ChangeLogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SkillTreeController;
use App\Http\Controllers\LocaleController;

Route::middleware([LocaleMiddleware::class])->group(function () {
    Route::get('/', HomeController::class)->name('Home');
    Route::get('/changelog', [ChangeLogController::class, 'index'])->name('Changelog');

    Route::get('/{class}', [SkillTreeController::class, 'show'])
        ->whereIn('class', SkillTreeController::CLASSES)
        ->name('skill-tree.show');
    Route::get('/locale/{locale}', LocaleController::class)
        ->whereIn('locale', array_keys(config('app.supported_locales')))
        ->name('locale.switch');

    Route::fallback(fn () => abort(404));
});
