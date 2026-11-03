<?php

use App\Modules\Demandes\Controllers\DemandeController;
use Illuminate\Support\Facades\Route;

/**
 * Routes du module Demandes de communication.
 */
Route::middleware('web')->group(function () {
    Route::post('/demandes', [DemandeController::class, 'store'])
        ->middleware(['auth', 'droit:demandes.saisir'])
        ->name('demandes.store');

    Route::post('/demandes/{id}/decider', [DemandeController::class, 'decider'])
        ->middleware(['auth', 'droit:demandes.decider'])
        ->whereNumber('id')
        ->name('demandes.decider');
});
