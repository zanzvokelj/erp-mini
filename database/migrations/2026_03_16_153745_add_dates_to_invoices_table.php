<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            if (!Schema::hasColumn('invoices', 'issued_at')) {
                $table->timestamp('issued_at')->nullable();
            }

            if (!Schema::hasColumn('invoices', 'due_at')) {
                $table->timestamp('due_at')->nullable();
            }

        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            if (Schema::hasColumn('invoices', 'issued_at')) {
                $table->dropColumn('issued_at');
            }

            if (Schema::hasColumn('invoices', 'due_at')) {
                $table->dropColumn('due_at');
            }

        });
    }
};
