<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        $defaultCompanyId = DB::table('companies')
            ->where('slug', 'default-company')
            ->value('id');

        if (! $defaultCompanyId) {
            $defaultCompanyId = DB::table('companies')->insertGetId([
                'name' => 'Default Company',
                'slug' => 'default-company',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        DB::table('users')
            ->whereNull('company_id')
            ->update([
                'company_id' => $defaultCompanyId,
                'updated_at' => $timestamp,
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        DB::table('companies')
            ->where('slug', 'default-company')
            ->delete();
    }
};
