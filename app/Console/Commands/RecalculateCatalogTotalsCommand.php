<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Scopes\CompanyScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando de mantenimiento SIN superficie HTTP — solo se ejecuta manualmente
 * u por cron desde el servidor. Alimenta el dashboard interno de operaciones
 * con totales agregados across-tenant. Requiere bypass deliberado del
 * CompanyScope del modelo Product porque, por definición, necesita leer
 * todas las empresas a la vez; ningún endpoint HTTP expone este resultado
 * por tenant cruzado.
 */
class RecalculateCatalogTotalsCommand extends Command
{
    protected $signature = 'catalog:recalculate-totals {--confirm}';

    protected $description = 'Recalcula totales de catálogo across-tenant para el dashboard de operaciones interno.';

    public function handle(): int
    {
        if (! $this->option('confirm')) {
            $this->error('Ejecuta con --confirm. Este comando lee todas las empresas a propósito (ver docblock de la clase).');

            return self::FAILURE;
        }

        Log::channel('single')->info('catalog:recalculate-totals ejecutado', [
            'user' => get_current_user(),
            'at' => now()->toIso8601String(),
        ]);

        $totalsByCompany = Product::withoutGlobalScope(CompanyScope::class)
            ->selectRaw('company_id, COUNT(*) as total, SUM(stock) as total_stock')
            ->groupBy('company_id')
            ->get();

        foreach ($totalsByCompany as $row) {
            $this->line("company_id={$row->company_id} total={$row->total} stock={$row->total_stock}");
        }

        return self::SUCCESS;
    }
}
