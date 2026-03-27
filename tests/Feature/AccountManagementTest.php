<?php

namespace Tests\Feature;

use App\Models\Account;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_account_via_web()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsAdmin();

        $response = $this->post('/finance/accounts', [
            'code' => '6100',
            'name' => 'Administrative Expense',
            'type' => 'expense',
            'category' => 'operating_expense',
            'subtype' => 'admin',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('finance.accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'code' => '6100',
            'category' => 'operating_expense',
            'subtype' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_account_via_api()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsAdmin();

        $account = Account::where('code', '5000')->firstOrFail();

        $response = $this->putJson("/api/v1/finance/accounts/{$account->id}", [
            'code' => '5000',
            'name' => 'Cost of Sales',
            'type' => 'expense',
            'category' => 'cost_of_sales',
            'subtype' => 'direct_cost',
            'is_active' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Cost of Sales')
            ->assertJsonPath('subtype', 'direct_cost');

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Cost of Sales',
            'subtype' => 'direct_cost',
        ]);
    }

    public function test_admin_can_toggle_account_status()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsAdmin();

        $account = Account::where('code', '5000')->firstOrFail();

        $response = $this->post(route('finance.accounts.toggle', $account));

        $response->assertRedirect();

        $this->assertFalse($account->fresh()->is_active);
    }
}
