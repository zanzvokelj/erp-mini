<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {

            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('supplier_id')
                ->constrained()
                ->cascadeOnDelete();

        });

        // set existing records to MAIN warehouse
        DB::table('purchase_orders')
            ->whereNull('warehouse_id')
            ->update(['warehouse_id' => 1]);

        // make column NOT NULL afterwards
        DB::statement("
            ALTER TABLE purchase_orders
            ALTER COLUMN warehouse_id SET NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {

            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');

        });
    }
};
