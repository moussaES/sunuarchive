<?php

use Illuminate\Support\Facades\Route;

Route::get('/styleguide', function () {
    return view('styleguide');
})->name('styleguide');
