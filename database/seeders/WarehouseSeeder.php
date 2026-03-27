<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::updateOrCreate(
            ['code' => 'WH-MAIN'],
            ['name' => 'Maribor Warehouse']
        );

        Warehouse::updateOrCreate(
            ['code' => 'WH-SEC'],
            ['name' => 'Celje Warehouse']
        );
    }
}
