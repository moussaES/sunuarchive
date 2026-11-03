<?php

use App\Modules\Recherche\Controllers\RechercheController;
use Illuminate\Support\Facades\Route;

/**
 * Routes du module Recherche d'actes (Section 9.4).
 */
Route::middleware('web')->group(function () {
    Route::get('/actes/recherche', [RechercheController::class, 'index'])
        ->middleware(['auth', 'droit:actes.consulter'])
        ->name('actes.recherche');
});
