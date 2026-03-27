<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number')->unique();
            $table->string('entry_type');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->text('description')->nullable();
            $table->timestamp('posted_at');
            $table->timestamps();

            $table->unique(['entry_type', 'reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
