<?php

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/**
 * Routes du module Authentification (connexion, déconnexion, verrouillage).
 */
Route::middleware('web')->group(function () {
    Route::get('/connexion', [AuthController::class, 'afficherConnexion'])->name('connexion');
    Route::get('/login', [AuthController::class, 'afficherConnexion'])->name('login');
    Route::post('/connexion', [AuthController::class, 'connecter'])->name('connexion.traiter');
    Route::post('/deconnexion', [AuthController::class, 'deconnecter'])->name('deconnexion');

    // Route de contrôle pour les tests de sécurité (exige registres.creer)
    Route::get('/test-protection-droit', function () {
        return response('OK', 200);
    })->middleware(['auth', 'droit:registres.creer']);
});
