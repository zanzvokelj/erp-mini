<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoUsersSeeder::class,
            AccountingSeeder::class,
            WarehouseSeeder::class,
            ErpSimulationSeeder::class,
        ]);
    }
}
