<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('reversal_of_journal_entry_id')
                ->nullable()
                ->after('reference_id')
                ->constrained('journal_entries')
                ->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('posted_at');
            $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversal_of_journal_entry_id');
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['reversed_at']);
        });
    }
};
