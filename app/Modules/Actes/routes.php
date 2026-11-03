<?php

use App\Modules\Actes\Controllers\ActeController;
use Illuminate\Support\Facades\Route;

/**
 * Routes du module Actes (fiches d'actes naissance/mariage/décès, transcription, doublons).
 */
Route::middleware('web')->group(function () {
    Route::get('/actes/{id}', [ActeController::class, 'show'])
        ->middleware(['auth', 'droit:actes.consulter'])
        ->whereNumber('id')
        ->name('actes.show');

    Route::post('/actes', [ActeController::class, 'store'])
        ->middleware(['auth', 'droit:actes.creer'])
        ->name('actes.store');

    Route::put('/actes/{id}', [ActeController::class, 'update'])
        ->middleware(['auth', 'droit:actes.modifier'])
        ->whereNumber('id')
        ->name('actes.update');
});
