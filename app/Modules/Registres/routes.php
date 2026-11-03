<?php

use App\Modules\Registres\Controllers\RegistreController;
use Illuminate\Support\Facades\Route;

/**
 * Routes du module Registres (inventaire, création, modification, consultation, export).
 */
Route::middleware('web')->group(function () {
    Route::get('/registres', [RegistreController::class, 'index'])
        ->name('registres.index');

    Route::post('/registres', [RegistreController::class, 'store'])
        ->middleware(['auth', 'droit:registres.creer'])
        ->name('registres.store');

    Route::put('/registres/{id}', [RegistreController::class, 'update'])
        ->middleware(['auth', 'droit:registres.modifier'])
        ->whereNumber('id')
        ->name('registres.update');

    Route::get('/registres/export/csv', [RegistreController::class, 'exporter'])
        ->middleware(['auth', 'droit:rapports.exporter'])
        ->name('registres.export.csv');
});
