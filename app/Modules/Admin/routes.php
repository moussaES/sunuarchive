<?php

use App\Modules\Admin\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

/**
 * Routes du module d'Administration (Section 9.6).
 */
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

    Route::post('/admin/utilisateurs', [AdminController::class, 'creerUtilisateur'])
        ->middleware('droit:utilisateurs.gerer')
        ->name('admin.utilisateurs.store');

    Route::put('/admin/utilisateurs/{id}', [AdminController::class, 'modifierUtilisateur'])
        ->middleware('droit:utilisateurs.gerer')
        ->whereNumber('id')
        ->name('admin.utilisateurs.update');

    Route::delete('/admin/utilisateurs/{id}', [AdminController::class, 'supprimerUtilisateur'])
        ->middleware('droit:utilisateurs.gerer')
        ->whereNumber('id')
        ->name('admin.utilisateurs.destroy');

    Route::post('/admin/utilisateurs/{id}/basculer', [AdminController::class, 'basculerEtatCompte'])
        ->middleware('droit:utilisateurs.gerer')
        ->whereNumber('id')
        ->name('admin.utilisateurs.basculer');

    Route::post('/admin/utilisateurs/{id}/debloquer', [AdminController::class, 'debloquerCompte'])
        ->middleware('droit:utilisateurs.gerer')
        ->whereNumber('id')
        ->name('admin.utilisateurs.debloquer');

    Route::post('/admin/sauvegardes/lancer', [AdminController::class, 'lancerSauvegarde'])
        ->middleware('droit:sauvegardes.gerer')
        ->name('admin.sauvegardes.lancer');

    Route::post('/admin/sauvegardes/tester', [AdminController::class, 'testerRestauration'])
        ->middleware('droit:sauvegardes.restaurer')
        ->name('admin.sauvegardes.tester');
});
