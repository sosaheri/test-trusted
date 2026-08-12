<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImportRunController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — starter-kit
|--------------------------------------------------------------------------
|
| Solo el stub de autenticación viene resuelto. Las rutas de catálogo
| (listado paginado, upload de CSV, estado de la corrida, apply) son
| responsabilidad del candidato — ver §3 del enunciado.
|
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/products', [ProductController::class, 'index']);

    Route::post('/import-runs', [ImportRunController::class, 'store']);
    Route::get('/import-runs/{importRun}', [ImportRunController::class, 'show']);
    Route::post('/import-runs/{importRun}/apply', [ImportRunController::class, 'apply']);
});
