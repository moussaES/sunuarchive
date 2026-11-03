<?php

use App\Modules\Rapports\Controllers\RapportsController;
use Illuminate\Support\Facades\Route;

/**
 * Routes du module Rapports (Section 9.2 & 11.10).
 */
Route::middleware('web')->group(function () {
    Route::get('/rapports/synthese-pdf', [RapportsController::class, 'exporterPdf'])
        ->middleware(['auth', 'droit:rapports.exporter'])
        ->name('rapports.pdf');

    Route::get('/rapports/synthese-csv', [RapportsController::class, 'exporterCsv'])
        ->middleware(['auth', 'droit:rapports.exporter'])
        ->name('rapports.csv');
});
