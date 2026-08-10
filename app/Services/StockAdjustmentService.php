<?php

namespace App\Services;

/**
 * Suma ajustes de stock preservando la escala DECIMAL(14,6) del esquema.
 * Usa bcmath en lugar de aritmética nativa de PHP: sumar floats aquí
 * introduciría pérdida de precisión silenciosa exactamente en el campo que
 * la prueba técnica prohíbe (§3.2 — stock DECIMAL(14,6), prohibido float).
 */
class StockAdjustmentService
{
    public function add(string $currentStock, string $delta): string
    {
        return bcadd($currentStock, $delta, 6);
    }
}
