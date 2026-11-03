<?php

use App\Modules\Fichiers\Controllers\FichierController;
use Illuminate\Support\Facades\Route;

/**
 * Routes du module Fichiers (consultation sécurisée, association, remplacement, intégrité SHA-256).
 */
Route::middleware('web')->group(function () {
    Route::get('/fichiers/{id}', [FichierController::class, 'show'])
        ->middleware(['auth', 'droit:fichiers.consulter'])
        ->whereNumber('id')
        ->name('fichiers.show');

    Route::post('/fichiers', [FichierController::class, 'store'])
        ->middleware(['auth', 'droit:fichiers.associer'])
        ->name('fichiers.store');

    Route::post('/fichiers/{id}/remplacer', [FichierController::class, 'remplacer'])
        ->middleware(['auth', 'droit:fichiers.remplacer'])
        ->whereNumber('id')
        ->name('fichiers.remplacer');

    Route::post('/fichiers/{id}/verifier-integrite', [FichierController::class, 'verifierIntegrite'])
        ->middleware(['auth', 'droit:fichiers.consulter'])
        ->whereNumber('id')
        ->name('fichiers.verifier-integrite');
});
