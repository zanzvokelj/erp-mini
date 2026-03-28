<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccountingSeeder::class,
            WarehouseSeeder::class,
        ]);

        if (filter_var(env('SEED_DEMO_USERS', false), FILTER_VALIDATE_BOOL)) {
            $this->call(DemoUsersSeeder::class);
        }

        if (filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL)) {
            $this->call(ErpSimulationSeeder::class);
        }
    }
}
