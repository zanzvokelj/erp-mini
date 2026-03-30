<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE varchar(32)");
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'sales'");
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("
                ALTER TABLE users
                ADD CONSTRAINT users_role_check
                CHECK (role IN ('admin', 'sales', 'warehouse', 'finance'))
            ");

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE users
                MODIFY role ENUM('admin', 'sales', 'warehouse', 'finance')
                NOT NULL DEFAULT 'sales'
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("UPDATE users SET role = 'sales' WHERE role = 'finance'");
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("
                ALTER TABLE users
                ADD CONSTRAINT users_role_check
                CHECK (role IN ('admin', 'sales', 'warehouse'))
            ");

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("UPDATE users SET role = 'sales' WHERE role = 'finance'");
            DB::statement("
                ALTER TABLE users
                MODIFY role ENUM('admin', 'sales', 'warehouse')
                NOT NULL DEFAULT 'sales'
            ");
        }
    }
};
