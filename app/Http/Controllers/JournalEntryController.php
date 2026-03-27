<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Services\LedgerService;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    public function __construct(
        protected LedgerService $ledgerService
    ) {
    }

    public function index(Request $request)
    {
        $entryTypes = JournalEntry::query()
            ->select('entry_type')
            ->distinct()
            ->orderBy('entry_type')
            ->pluck('entry_type');

        $referenceTypes = JournalEntry::query()
            ->select('reference_type')
            ->distinct()
            ->orderBy('reference_type')
            ->pluck('reference_type');

        $entries = JournalEntry::with(['lines.account', 'reversalEntry'])
            ->when($request->filled('entry_type'), function ($query) use ($request) {
                $query->where('entry_type', $request->string('entry_type'));
            })
            ->when($request->filled('reference_type'), function ($query) use ($request) {
                $query->where('reference_type', $request->string('reference_type'));
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('posted_at', '>=', $request->string('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('posted_at', '<=', $request->string('date_to'));
            })
            ->latest('posted_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('finance.journal-entries', [
            'entries' => $entries,
            'entryTypes' => $entryTypes,
            'referenceTypes' => $referenceTypes,
        ]);
    }

    public function reverse(Request $request, JournalEntry $entry)
    {
        try {
            $this->ledgerService->reverse($entry, $request->user());

            return back()->with('success', "Journal entry {$entry->entry_number} reversed.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
