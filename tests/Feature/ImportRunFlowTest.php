<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ImportRun;
use App\Models\ImportRunItem;
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

    public function test_apply_promotes_valid_items_and_marks_run_as_applied(): void
    {
        $company = Company::create(['name' => 'Empresa Aplicación']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Laura',
            'email' => 'laura@empresa-aplicacion.test',
            'password' => Hash::make('password'),
        ]);

        $importRun = ImportRun::create([
            'company_id' => $company->id,
            'file_path' => 'imports/catalogo.csv',
            'status' => 'validated',
            'total_rows' => 2,
            'valid_rows' => 1,
            'rejected_rows' => 1,
        ]);

        ImportRunItem::create([
            'import_run_id' => $importRun->id,
            'row_number' => 1,
            'status' => 'valid',
            'data' => [
                'name' => 'Producto validado',
                'sku' => 'SKU-APPLY-1',
                'price' => '18.75',
                'stock' => '10.500000',
            ],
            'errors' => [],
        ]);

        ImportRunItem::create([
            'import_run_id' => $importRun->id,
            'row_number' => 2,
            'status' => 'rejected',
            'data' => [
                'name' => 'Producto rechazado',
                'sku' => '',
                'price' => '',
                'stock' => '5',
            ],
            'errors' => ['El SKU es obligatorio.'],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/import-runs/' . $importRun->id . '/apply');

        $response->assertOk()
            ->assertJsonPath('import_run.status', 'applied');

        $this->assertDatabaseHas('products', [
            'company_id' => $company->id,
            'sku' => 'SKU-APPLY-1',
            'name' => 'Producto validado',
        ]);

        $this->assertDatabaseHas('import_runs', [
            'id' => $importRun->id,
            'status' => 'applied',
        ]);
    }

    public function test_apply_is_idempotent_for_the_same_import_run(): void
    {
        $company = Company::create(['name' => 'Empresa Idempotente']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Diana',
            'email' => 'diana@empresa-idempotente.test',
            'password' => Hash::make('password'),
        ]);

        $importRun = ImportRun::create([
            'company_id' => $company->id,
            'file_path' => 'imports/catalogo.csv',
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'rejected_rows' => 0,
        ]);

        ImportRunItem::create([
            'import_run_id' => $importRun->id,
            'row_number' => 1,
            'status' => 'valid',
            'data' => [
                'name' => 'Producto único',
                'sku' => 'SKU-IDEMPOTENTE',
                'price' => '9.99',
                'stock' => '7.500000',
            ],
            'errors' => [],
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/import-runs/' . $importRun->id . '/apply')
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/import-runs/' . $importRun->id . '/apply')
            ->assertOk();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'company_id' => $company->id,
            'sku' => 'SKU-IDEMPOTENTE',
        ]);
    }

    public function test_catalog_listing_is_scoped_to_the_authenticated_company(): void
    {
        $companyA = Company::create(['name' => 'Empresa A']);
        $companyB = Company::create(['name' => 'Empresa B']);
        $user = User::create([
            'company_id' => $companyA->id,
            'name' => 'Marco',
            'email' => 'marco@empresa-a.test',
            'password' => Hash::make('password'),
        ]);

        Product::create([
            'company_id' => $companyA->id,
            'sku' => 'SKU-ALPHA',
            'name' => 'Producto A',
            'price' => '10.50',
            'stock' => '20.000000',
        ]);

        Product::create([
            'company_id' => $companyB->id,
            'sku' => 'SKU-BETA',
            'name' => 'Producto B',
            'price' => '11.50',
            'stock' => '15.000000',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/products?search=SKU&per_page=15');

        $response->assertOk()
            ->assertJsonPath('data.0.sku', 'SKU-ALPHA');

        $this->assertDatabaseCount('products', 2);
        $this->assertSame(1, collect($response->json('data'))->count());
    }

    public function test_dirty_csv_import_cycle_reports_rejections_and_keeps_the_run_validated(): void
    {
        $company = Company::create(['name' => 'Empresa CSV Sucia']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Nora',
            'email' => 'nora@empresa-csv-sucia.test',
            'password' => Hash::make('password'),
        ]);

        $csv = <<<'CSV'
name,sku,price,stock
Producto válido,SKU-OK-1,12.50,5
Producto sin SKU,,14.00,2
SKU duplicado,SKU-OK-1,20.00,1
Producto con precio negativo,SKU-OK-2,-3,8
CSV;

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/import-runs', [
                'file' => UploadedFile::fake()->createWithContent('catalogo_sucio.csv', $csv),
            ]);

        $response->assertStatus(202)
            ->assertJsonPath('import_run.company_id', $company->id);

        $importRun = ImportRun::query()->firstOrFail();

        $this->assertSame('validated', $importRun->status);
        $this->assertSame(4, $importRun->total_rows);
        $this->assertSame(2, $importRun->valid_rows);
        $this->assertSame(2, $importRun->rejected_rows);

        $this->assertDatabaseHas('import_run_items', [
            'import_run_id' => $importRun->id,
            'status' => 'rejected',
        ]);

        $showResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/import-runs/' . $importRun->id);

        $showResponse->assertOk()
            ->assertJsonPath('id', $importRun->id)
            ->assertJsonPath('status', 'validated')
            ->assertJsonPath('rejected_rows', 2);
    }
}
