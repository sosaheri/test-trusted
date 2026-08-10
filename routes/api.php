<?php

use App\Http\Controllers\AuthController;
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

    // TODO (candidato):
    //   GET  /products              -> listado paginado/ordenado/buscado, server-side, scoped a company_id
    //   POST /import-runs           -> recibe el CSV, encola el Job, responde con import_run_id (202)
    //   GET  /import-runs/{id}      -> estado + resumen (para polling)
    //   POST /import-runs/{id}/apply -> promueve staging -> catálogo real (idempotente)
});
