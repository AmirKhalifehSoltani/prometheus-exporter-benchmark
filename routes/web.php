<?php

use App\Http\Controllers\PrometheusExporterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/metrics/redis', [PrometheusExporterController::class, 'exportWithRedis']);
Route::get('/metrics/file', [PrometheusExporterController::class, 'exportWithFile']);