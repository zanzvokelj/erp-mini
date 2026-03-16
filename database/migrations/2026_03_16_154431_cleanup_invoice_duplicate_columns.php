<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            if (Schema::hasColumn('invoices', 'tax_total')) {
                $table->dropColumn('tax_total');
            }

            if (Schema::hasColumn('invoices', 'due_at')) {
                $table->dropColumn('due_at');
            }

        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            $table->decimal('tax_total', 12, 2)->default(0);
            $table->timestamp('due_at')->nullable();

        });
    }
};
