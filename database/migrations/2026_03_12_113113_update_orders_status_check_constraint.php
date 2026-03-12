<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_status_check');

        DB::statement("
            ALTER TABLE orders
            ADD CONSTRAINT orders_status_check
            CHECK (
                status IN (
                    'draft',
                    'confirmed',
                    'shipped',
                    'completed',
                    'cancelled',
                    'returned'
                )
            )
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_status_check');

        DB::statement("
            ALTER TABLE orders
            ADD CONSTRAINT orders_status_check
            CHECK (
                status IN (
                    'draft',
                    'confirmed',
                    'shipped',
                    'completed',
                    'cancelled'
                )
            )
        ");
    }
};
