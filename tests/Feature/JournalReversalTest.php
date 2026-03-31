<?php

namespace Tests\Feature;

use App\Accounting\AccountingEntryTypes;
use App\Models\Invoice;
use App\Models\JournalEntry;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalReversalTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_entry_can_be_reversed()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('finance');

        $original = JournalEntry::create([
            'entry_number' => 'JE-000001',
            'entry_type' => AccountingEntryTypes::INVOICE_ISSUED,
            'reference_type' => Invoice::class,
            'reference_id' => 1,
            'description' => 'Original entry',
            'posted_at' => now(),
        ]);

        $original->lines()->createMany([
            ['account_id' => \App\Models\Account::where('code', '1100')->value('id'), 'debit' => 100, 'credit' => 0, 'line_number' => 1],
            ['account_id' => \App\Models\Account::where('code', '4000')->value('id'), 'debit' => 0, 'credit' => 100, 'line_number' => 2],
        ]);

        $response = $this->post(route('finance.journal-entries.reverse', $original));

        $response->assertRedirect();

        $reversal = JournalEntry::where('reversal_of_journal_entry_id', $original->id)->first();

        $this->assertNotNull($reversal);
        $this->assertEquals(AccountingEntryTypes::MANUAL_REVERSAL, $reversal->entry_type);
        $this->assertEquals(100.0, (float) $reversal->lines->sum('debit'));
        $this->assertEquals(100.0, (float) $reversal->lines->sum('credit'));

        $this->assertDatabaseHas('journal_entries', [
            'id' => $original->id,
        ]);
        $this->assertNotNull($original->fresh()->reversed_at);
    }
}
