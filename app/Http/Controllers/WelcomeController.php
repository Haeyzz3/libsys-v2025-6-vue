<?php

namespace App\Http\Controllers;

use App\Models\BorrowingTransaction;
use App\Models\LibraryVisit;
use App\Models\Record;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();
        $lastWeek = $now->copy()->subWeek();

        // Collections
        $totalCollections = Record::count();
        $newThisMonth = Record::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->count();
        $lastMonthCount = Record::whereYear('created_at', $lastMonth->year)
            ->whereMonth('created_at', $lastMonth->month)
            ->count();

        $percentChangeThisMonth = $lastMonthCount > 0
            ? round(($newThisMonth - $lastMonthCount) / $lastMonthCount * 100)
            : 100;

        // Borrowing Transactions
        $totalBorrowings = BorrowingTransaction::count();
        $lastWeekCount = BorrowingTransaction::where('created_at', '>=', $lastWeek)->count();
        $weekChange = $lastWeekCount;

        return Inertia::render('Welcome', [
            'totalCollections' => $totalCollections,
            'newThisMonth' => $newThisMonth,
            'percentChangeThisMonth' => $percentChangeThisMonth,
            'totalBorrowings' => $totalBorrowings,
            'weekChange' => $weekChange,
        ]);
    }

    public function searchRecords(Request $request)
    {
        // Validate the search query and optional type filter
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'type' => 'nullable|string|in:book,digital_resource,periodical,thesis'
        ]);

        $searchQuery = $request->get('q');
        $typeFilter = $request->get('type');
        // Remove the artificial limit - let frontend handle grouping first, then limit if needed
        // The limit was preventing proper grouping when there are more than 5 copies of the same title

        try {
            $query = Record::query()
                ->whereNull('deleted_at')
                ->where(function ($query) use ($searchQuery) {
                    $query->where('title', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('accession_number', 'LIKE', "%{$searchQuery}%");
                });

            // Apply type filter if specified
            if ($typeFilter) {
                switch ($typeFilter) {
                    case 'book':
                        $query->whereHas('book');
                        break;
                    case 'digital_resource':
                        $query->whereHas('digitalResource');
                        break;
                    case 'periodical':
                        $query->whereHas('periodical');
                        break;
                    case 'thesis':
                        $query->whereHas('thesis');
                        break;
                }
            }

            $records = $query
                ->with(['book', 'digitalResource', 'periodical', 'thesis']) // Eager load relations
                ->orderBy('title', 'asc')
                ->limit(200) // Higher limit to allow proper grouping while preventing performance issues
                ->get();

            return response()->json([
                'success' => true,
                'records' => $records,
                'count' => $records->count(),
                'query' => $searchQuery,
                'type_filter' => $typeFilter
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
