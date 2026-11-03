<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Schedule::command('sigaec:sauvegarde')
    ->weekdays()
    ->at('07:30')
    ->description('Sauvegarde quotidienne des archives municipales et de la base de données à 07:30');
