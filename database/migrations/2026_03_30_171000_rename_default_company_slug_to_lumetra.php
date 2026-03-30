<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('companies')
            ->where('slug', 'default-company')
            ->update([
                'name' => 'Lumetra',
                'slug' => 'lumetra',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('companies')
            ->where('slug', 'lumetra')
            ->update([
                'name' => 'Default Company',
                'slug' => 'default-company',
                'updated_at' => now(),
            ]);
    }
};
