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
            throw new RuntimeException('Default company must exist before backfilling finance data.');
        }

        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('accounting_periods', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('journal_lines', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        DB::table('accounts')
            ->whereNull('company_id')
            ->update(['company_id' => $defaultCompanyId]);

        DB::table('accounting_periods')
            ->whereNull('company_id')
            ->update(['company_id' => $defaultCompanyId]);

        DB::statement('
            UPDATE journal_entries
            SET company_id = invoices.company_id
            FROM invoices
            WHERE journal_entries.reference_type = \'App\\\\Models\\\\Invoice\'
              AND journal_entries.reference_id = invoices.id
              AND journal_entries.company_id IS NULL
        ');

        DB::statement('
            UPDATE journal_entries
            SET company_id = payments.company_id
            FROM payments
            WHERE journal_entries.reference_type = \'App\\\\Models\\\\Payment\'
              AND journal_entries.reference_id = payments.id
              AND journal_entries.company_id IS NULL
        ');

        DB::statement('
            UPDATE journal_entries
            SET company_id = purchase_orders.company_id
            FROM purchase_orders
            WHERE journal_entries.reference_type = \'App\\\\Models\\\\PurchaseOrder\'
              AND journal_entries.reference_id = purchase_orders.id
              AND journal_entries.company_id IS NULL
        ');

        DB::statement('
            UPDATE journal_entries
            SET company_id = supplier_payments.company_id
            FROM supplier_payments
            WHERE journal_entries.reference_type = \'App\\\\Models\\\\SupplierPayment\'
              AND journal_entries.reference_id = supplier_payments.id
              AND journal_entries.company_id IS NULL
        ');

        DB::statement('
            UPDATE journal_entries
            SET company_id = orders.company_id
            FROM orders
            WHERE journal_entries.reference_type = \'App\\\\Models\\\\Order\'
              AND journal_entries.reference_id = orders.id
              AND journal_entries.company_id IS NULL
        ');

        DB::table('journal_entries')
            ->whereNull('company_id')
            ->update(['company_id' => $defaultCompanyId]);

        DB::statement('
            UPDATE journal_lines
            SET company_id = journal_entries.company_id
            FROM journal_entries
            WHERE journal_lines.journal_entry_id = journal_entries.id
              AND journal_lines.company_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('accounting_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
