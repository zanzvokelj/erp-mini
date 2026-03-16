<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::create([
            'name' => 'Main Distribution Center',
            'code' => 'WH-MAIN'
        ]);

        Warehouse::create([
            'name' => 'Secondary Storage',
            'code' => 'WH-SEC'
        ]);
    }
}
