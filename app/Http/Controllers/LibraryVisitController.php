<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LibraryVisit;
use App\Models\User;
use App\Models\VisitPurpose;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LibraryVisitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Inertia\Response
    {
        $perPage = $request->input('per_page', 10);
        $sortField = $request->input('sort_field', null);
        $sortDirection = $request->input('sort_direction', 'desc');
        $filters = [];

        // Capture search parameters
        $searchTerm = $request->input('search');
        if (!empty($searchTerm)) {
            $filters[] = [
                'id' => 'search',
                'value' => $searchTerm
            ];
        }

        // Capture purpose filter
        $visitPurposes = $request->input('visit_purpose_id');
        if (!empty($visitPurposes)) {
            $filters[] = [
                'id' => 'visit_purpose_id',
                'value' => is_array($visitPurposes) ? $visitPurposes : [$visitPurposes]
            ];
        }

        $visits = LibraryVisit::with('user', 'visitPurpose')
            ->when($searchTerm, function ($query, $searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('id', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                            $userQuery->where('first_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $searchTerm . '%');
                        });
                });
            })
            ->when($visitPurposes, function ($query, $visitPurposes) {
                $purposes = is_array($visitPurposes) ? $visitPurposes : [$visitPurposes];
                $query->whereIn('visit_purpose_id', $purposes);
            })
            ->when($sortField, function ($query, $sortField) use ($sortDirection) {
                $query->orderBy($sortField, $sortDirection);
            }, function ($query) {
                $query->latest();
            })
            ->paginate(perPage: $perPage);

        // Fetch available purposes for the filter dropdown
        $availablePurposes = VisitPurpose::select('id', 'name')->get()->map(function ($purpose) {
            return [
                'value' => (string) $purpose->id, // Cast to string for frontend compatibility
                'label' => $purpose->name,
            ];
        })->toArray();

        return Inertia::render('logger/Index', [
            'data' => $visits,
            'filter' => $filters,
            'currentSortField' => $sortField,
            'currentSortDirection' => $sortDirection,
            'availablePurposes' => $availablePurposes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return Inertia::render('library-visit/Create', [
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $success_message = '';

        $user = User::where('id', $request->patron_id)->first();
        if (!$user) {
            session()->flash('error', 'User not found');
            return to_route('logger.create');
        }

        try {
            if (!$request->purpose_id) {
                $user_entry = LibraryVisit::where('user_id', $request->patron_id)->whereNull('exit_time')->first();
                if ($user_entry) {
                    $user_entry->update([
                        'exit_time' => now(),
                    ]);
                    $success_message = 'Thank you for visiting USeP Library!';
                } else {
                    $success_message = 'No active session found.';
                }
            } else {
                $purpose = VisitPurpose::where('id', $request->purpose_id)->first();
                if ($purpose) {
                    LibraryVisit::create([
                        'user_id' => $user->id,
                        'entry_time' => now(),
                        'visit_purpose_id' => $purpose->id,
                    ]);
                    $success_message = 'Welcome to USeP Library!';
                } else {
                    $success_message = 'Invalid purpose selected';
                }
            }
            session()->flash('success', $success_message);
        } catch (\Exception $e) {
            session()->flash('error', 'Something went wrong');
            \Log::error('Error: ' . $e->getMessage(), [
                'user_id' => $request->patron_id,
                'purpose_id' => $request->purpose_id,
                'exception' => $e
            ]);
        }
        return to_route('logger.create');
    }

    public function storeTransaction(Request $request): ?JsonResponse
    {
        try {
            if ($request->transaction_type === 'logout') {
                $user_entry = LibraryVisit::where('user_id', $request->user_id)->whereNull('exit_time')->first();
                if (!$user_entry) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No active visit to logout.'
                    ], 404);
                }
                $user_entry->update([
                    'exit_time' => now(),
                ]);
            } elseif ($request->transaction_type === 'login') {
                $user = User::find($request->user_id);
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found.'
                    ], 404);
                }
                LibraryVisit::create([
                    'user_id' => $request->user_id,
                    'entry_time' => now(),
                ]);
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchById(Request $request): JsonResponse
    {
        // Validate the library_id query
        $request->validate([
            'library_id' => 'required|numeric'
        ]);

        $libraryId = $request->get('library_id');

        try {
            $user = User::query()
                ->where('library_id', $libraryId)
                ->first(['id', 'first_name', 'last_name', 'library_id', 'email']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user found with this library number'
                ], 404);
            }

            // Check for today's library visit record
            $today = now()->startOfDay();
            $todayEnd = now()->endOfDay();

            $todayVisit = LibraryVisit::query()
                ->where('user_id', $user->id)
                ->whereBetween('entry_time', [$today, $todayEnd])
                ->orderBy('entry_time', 'desc')
                ->first(['entry_time', 'exit_time']);

            // Determine transaction type based on visit record
            $transactionType = 'login'; // Default for no record today

            if ($todayVisit) {
                // User has a record today
                if (is_null($todayVisit->exit_time)) {
                    // Has entry but no exit - next action should be logout
                    $transactionType = 'logout';
                } else {
                    // Has both entry and exit - next action should be login
                    $transactionType = 'login';
                }
            }

            // Prepare user data without the internal id
            $userData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'library_id' => $user->library_id,
                'email' => $user->email,
                'transaction_type' => $transactionType
            ];

            return response()->json([
                'success' => true,
                'user' => $userData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function scannerLookup(Request $request)
    {
        // Validate the library_id query
        $request->validate([
            'scanned_id' => 'required|numeric'
        ]);

        $scannedId = $request->get('scanned_id');

        try {
            $user = User::query()
                ->where('card_number', $scannedId)
                ->first(['id', 'first_name', 'last_name', 'library_id', 'email']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user found with this library number'
                ], 404);
            }

            // Check for today's library visit record
            $today = now()->startOfDay();
            $todayEnd = now()->endOfDay();

            $todayVisit = LibraryVisit::query()
                ->where('user_id', $user->id)
                ->whereBetween('entry_time', [$today, $todayEnd])
                ->orderBy('entry_time', 'desc')
                ->first(['entry_time', 'exit_time']);

            // Determine transaction type based on visit record
            $transactionType = 'login'; // Default for no record today

            if ($todayVisit) {
                // User has a record today
                if (is_null($todayVisit->exit_time)) {
                    // Has entry but no exit - next action should be logout
                    $transactionType = 'logout';
                } else {
                    // Has both entry and exit - next action should be login
                    $transactionType = 'login';
                }
            }

            // Prepare user data without the internal id
            $userData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'library_id' => $user->library_id,
                'email' => $user->email,
                'transaction_type' => $transactionType
            ];

            return response()->json([
                'success' => true,
                'user' => $userData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchByName(Request $request): ?JsonResponse
    {
        // Validate the search query
        $request->validate([
            'q' => 'required|string|min:2|max:255'
        ]);

        $searchQuery = $request->get('q');
        $limit = $request->get('limit', 5); // Limit results to prevent overwhelming UI

        try {
            $query = User::query()
                ->whereHas('userType', function ($q) {
                    $q->where('name', 'faculty');
                })
                ->where(function ($query) use ($searchQuery) {
                    $query->where('first_name', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('last_name', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('email', 'LIKE', "%{$searchQuery}%");
                });

            $users = $query
                ->orderBy('first_name', 'asc')
                ->limit($limit)
                ->get(['id', 'first_name', 'last_name', 'library_id', 'email']);

            // Get today's date range
            $today = now()->startOfDay();
            $todayEnd = now()->endOfDay();

            // Get user IDs for batch query
            $userIds = $users->pluck('id');

            // Batch query for today's visits to avoid N+1 problem
            $todayVisits = LibraryVisit::query()
                ->whereIn('user_id', $userIds)
                ->whereBetween('entry_time', [$today, $todayEnd])
                ->orderBy('user_id')
                ->orderBy('entry_time', 'desc')
                ->get(['user_id', 'entry_time', 'exit_time'])
                ->groupBy('user_id')
                ->map(function ($visits) {
                    // Get the most recent visit for each user
                    return $visits->first();
                });

            // Transform users data and add transaction_type
            $usersData = $users->map(function ($user) use ($todayVisits) {
                $todayVisit = $todayVisits->get($user->id);

                // Determine transaction type based on visit record
                $transactionType = 'login'; // Default for no record today

                if ($todayVisit) {
                    // User has a record today
                    if (is_null($todayVisit->exit_time)) {
                        // Has entry but no exit - next action should be logout
                        $transactionType = 'logout';
                    } else {
                        // Has both entry and exit - next action should be login
                        $transactionType = 'login';
                    }
                }

                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'library_id' => $user->library_id,
                    'email' => $user->email,
                    'transaction_type' => $transactionType
                ];
            });

            return response()->json([
                'success' => true,
                'users' => $usersData,
                'count' => $usersData->count(),
                'query' => $searchQuery
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function visitCardStats(Request $request)
    {
        $filter = $request->get('filter', 'day'); // day|week|month|custom
        $from = $request->get('custom_from');
        $to = $request->get('custom_to');

        // Determine period
        $now = Carbon::now();
        switch ($filter) {
            case 'week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'custom':
                if ($from && $to) {
                    $start = Carbon::parse($from)->startOfDay();
                    $end = Carbon::parse($to)->endOfDay();
                } else {
                    // Fallback to day if custom invalid
                    $filter = 'day';
                    $start = $now->copy()->startOfDay();
                    $end = $now->copy()->endOfDay();
                }
                break;
            case 'day':
            default:
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
        }

        // Build base join for students (user_type_id 3=undergrad, 4=grad)
        $baseJoins = function($query) {
            $query->join('users', 'library_visits.user_id', '=', 'users.id')
                ->leftJoin('undergraduate_students', 'undergraduate_students.user_id', '=', 'users.id')
                ->leftJoin('graduate_students', 'graduate_students.user_id', '=', 'users.id')
                ->leftJoin('courses', function($join) {
                    $join->on('courses.id', '=', 'undergraduate_students.course_id')
                        ->orOn('courses.id', '=', 'graduate_students.course_id');
                })
                ->whereIn('users.user_type_id', [3,4]);
        };

        // Currently in library (open visits)
        $currentQuery = LibraryVisit::query();
        $baseJoins($currentQuery);
        $currentQuery->whereNull('library_visits.exit_time');
        $currentInLibraryRaw = $currentQuery->selectRaw('COALESCE(courses.code, "UNASSIGNED") as program_code, COUNT(DISTINCT library_visits.user_id) as cnt')
            ->groupBy('program_code')
            ->get();
        $currentlyInLibrary = [];
        $totalCurrent = 0;
        foreach ($currentInLibraryRaw as $row) {
            $currentlyInLibrary[$row->program_code] = (int) $row->cnt;
            $totalCurrent += (int) $row->cnt;
        }
        $currentlyInLibrary['all'] = $totalCurrent;

        // Visits within period (count each visit/entry)
        $visitQuery = LibraryVisit::query();
        $baseJoins($visitQuery);
        $visitQuery->whereBetween('library_visits.entry_time', [$start, $end]);
        $visitsRaw = $visitQuery->selectRaw('COALESCE(courses.code, "UNASSIGNED") as program_code, COUNT(*) as cnt')
            ->groupBy('program_code')
            ->get();
        $visits = [];
        $totalVisits = 0;
        foreach ($visitsRaw as $row) {
            $visits[$row->program_code] = (int) $row->cnt;
            $totalVisits += (int) $row->cnt;
        }
        $visits['all'] = $totalVisits;

        // Program list (only include programs that exist in courses table for now)
        $programs = Course::select('code','name')->orderBy('code')->get()->map(function($c){
            return ['code' => $c->code, 'name' => $c->name];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'programs' => $programs,
                'currently_in_library' => $currentlyInLibrary,
                'visits' => $visits,
                'period' => [
                    'filter' => $filter,
                    'start' => $start->toDateTimeString(),
                    'end' => $end->toDateTimeString(),
                ],
            ],
        ]);
    }
}
