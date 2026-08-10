<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rama feat/ai-generated-importer: agrega el importador generado por IA
| sobre el starter-kit base. Revísalo como el PR de un compañero (§5.3).
|
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/suggest', [ProductController::class, 'suggest']);
    Route::post('/products', [ProductController::class, 'store']);

    Route::post('/imports', [ImportController::class, 'store']);
});
