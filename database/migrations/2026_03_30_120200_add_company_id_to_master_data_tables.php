<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultCompanyId = DB::table('companies')
            ->where('slug', 'default-company')
            ->value('id');

        if (! $defaultCompanyId) {
            throw new RuntimeException('Default company must exist before backfilling master data.');
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        DB::table('customers')
            ->whereNull('company_id')
            ->update(['company_id' => $defaultCompanyId]);

        DB::table('suppliers')
            ->whereNull('company_id')
            ->update(['company_id' => $defaultCompanyId]);

        DB::table('warehouses')
            ->whereNull('company_id')
            ->update(['company_id' => $defaultCompanyId]);

        DB::table('products')
            ->whereNull('company_id')
            ->update(['company_id' => $defaultCompanyId]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
