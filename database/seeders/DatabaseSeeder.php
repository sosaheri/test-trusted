<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dos empresas semilla (company_id 1 y 2), cada una con un usuario stub.
 * A propósito NO se crea ningún producto: el candidato define ese esquema.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $empresaUno = Company::create(['name' => 'Empresa Uno C.A.']);
        $empresaDos = Company::create(['name' => 'Empresa Dos C.A.']);

        User::create([
            'company_id' => $empresaUno->id,
            'name' => 'Ana (Empresa Uno)',
            'email' => 'ana@empresa-uno.test',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'company_id' => $empresaDos->id,
            'name' => 'Beto (Empresa Dos)',
            'email' => 'beto@empresa-dos.test',
            'password' => Hash::make('password'),
        ]);
    }
}
