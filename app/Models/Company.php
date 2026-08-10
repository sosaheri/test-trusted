<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Nota para el candidato: el modelo Product y su relación con Company
    // son responsabilidad tuya (§3.2 — aislamiento por company_id en el
    // esquema). No se provee aquí a propósito.
}
