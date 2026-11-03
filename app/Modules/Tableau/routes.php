<?php

use App\Modules\Tableau\Controllers\TableauController;
use Illuminate\Support\Facades\Route;

/**
 * Routes du module Tableau de bord (Section 9.2).
 */
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/', [TableauController::class, 'index'])->name('tableau.index');
});
