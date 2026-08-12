<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class CompanyContextService
{
    public function resolve(): int
    {
        $companyId = Auth::user()?->company_id;

        if ($companyId === null) {
            abort(403, 'No se pudo resolver la empresa del usuario autenticado.');
        }

        return (int) $companyId;
    }

    public function assertSame(int $companyId): int
    {
        $currentCompanyId = $this->resolve();

        if ($currentCompanyId !== $companyId) {
            abort(403, 'La empresa del usuario no coincide con la corrida de importación.');
        }

        return $currentCompanyId;
    }
}
