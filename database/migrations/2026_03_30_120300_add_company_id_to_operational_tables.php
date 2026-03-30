<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('stock_reservations', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        DB::statement('
            UPDATE orders
            SET company_id = customers.company_id
            FROM customers
            WHERE orders.customer_id = customers.id
              AND orders.company_id IS NULL
        ');

        DB::statement('
            UPDATE purchase_orders
            SET company_id = suppliers.company_id
            FROM suppliers
            WHERE purchase_orders.supplier_id = suppliers.id
              AND purchase_orders.company_id IS NULL
        ');

        DB::statement('
            UPDATE stock_movements
            SET company_id = products.company_id
            FROM products
            WHERE stock_movements.product_id = products.id
              AND stock_movements.company_id IS NULL
        ');

        DB::statement('
            UPDATE stock_reservations
            SET company_id = orders.company_id
            FROM orders
            WHERE stock_reservations.order_id = orders.id
              AND stock_reservations.company_id IS NULL
        ');

        DB::statement('
            UPDATE invoices
            SET company_id = orders.company_id
            FROM orders
            WHERE invoices.order_id = orders.id
              AND invoices.company_id IS NULL
        ');

        DB::statement('
            UPDATE payments
            SET company_id = invoices.company_id
            FROM invoices
            WHERE payments.invoice_id = invoices.id
              AND payments.company_id IS NULL
        ');

        DB::statement('
            UPDATE supplier_payments
            SET company_id = purchase_orders.company_id
            FROM purchase_orders
            WHERE supplier_payments.purchase_order_id = purchase_orders.id
              AND supplier_payments.company_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('stock_reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
