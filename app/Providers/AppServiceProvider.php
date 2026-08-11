<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Sanctum trae su propia migración de personal_access_tokens y la
        // carga durante el boot() de SanctumServiceProvider — que corre
        // antes que el boot() de este provider. Por eso ignoreMigrations()
        // va en register(): todos los register() terminan antes de que
        // arranque cualquier boot(). La ignoramos porque este starter-kit
        // define su propia migración explícita en database/migrations/,
        // para que el candidato tenga visibilidad completa del esquema sin
        // ir a buscarla en vendor/.
        Sanctum::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
