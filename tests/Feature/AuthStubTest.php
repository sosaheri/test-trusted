<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthStubTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_user_can_log_in_and_receives_a_token(): void
    {
        $company = Company::create(['name' => 'Empresa Test']);
        User::create([
            'company_id' => $company->id,
            'name' => 'Tester',
            'email' => 'tester@empresa-test.test',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'tester@empresa-test.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.company_id', $company->id)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $company = Company::create(['name' => 'Empresa Test']);
        User::create([
            'company_id' => $company->id,
            'name' => 'Tester',
            'email' => 'tester@empresa-test.test',
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'tester@empresa-test.test',
            'password' => 'incorrecta',
        ])->assertStatus(401);
    }
}
