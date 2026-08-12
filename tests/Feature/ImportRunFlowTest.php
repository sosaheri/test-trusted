<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportRunFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_an_import_run(): void
    {
        $company = Company::create(['name' => 'Empresa Uno']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Ana',
            'email' => 'ana@empresa-uno.test',
            'password' => Hash::make('password'),
        ]);

        $csv = <<<'CSV'
name,sku,price,stock
Producto A,SKU-001,10.50,20
Producto B,SKU-002,8.25,15
CSV;

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/import-runs', [
                'file' => UploadedFile::fake()->createWithContent('catalogo.csv', $csv),
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'message',
                'import_run' => ['id', 'status', 'company_id'],
            ]);

        $this->assertDatabaseHas('import_runs', [
            'company_id' => $company->id,
            'status' => 'validated',
        ]);
    }

    public function test_same_sku_can_exist_in_different_companies(): void
    {
        $companyA = Company::create(['name' => 'Empresa A']);
        $companyB = Company::create(['name' => 'Empresa B']);

        Product::create([
            'company_id' => $companyA->id,
            'sku' => 'SKU-UNICO',
            'name' => 'Producto A',
            'price' => '10.50',
            'stock' => '20.000000',
        ]);

        Product::create([
            'company_id' => $companyB->id,
            'sku' => 'SKU-UNICO',
            'name' => 'Producto B',
            'price' => '11.25',
            'stock' => '18.500000',
        ]);

        $this->assertDatabaseCount('products', 2);
    }
}
