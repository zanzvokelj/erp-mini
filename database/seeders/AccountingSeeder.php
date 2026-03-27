<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => '1000', 'name' => 'Cash', 'type' => 'asset', 'category' => 'current_asset', 'subtype' => 'cash'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'category' => 'current_asset', 'subtype' => 'trade_receivables'],
            ['code' => '1200', 'name' => 'Inventory Asset', 'type' => 'asset', 'category' => 'current_asset', 'subtype' => 'inventory'],
            ['code' => '1300', 'name' => 'Input VAT Receivable', 'type' => 'asset', 'category' => 'current_asset', 'subtype' => 'vat_receivable'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'category' => 'current_liability', 'subtype' => 'trade_payables'],
            ['code' => '2100', 'name' => 'Output VAT Payable', 'type' => 'liability', 'category' => 'current_liability', 'subtype' => 'vat_payable'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'revenue', 'category' => 'operating_revenue', 'subtype' => 'product_sales'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'category' => 'cost_of_sales', 'subtype' => 'inventory_cost'],
        ];

        foreach ($accounts as $account) {
            Account::updateOrCreate(
                ['code' => $account['code']],
                $account
            );
        }
    }
}
