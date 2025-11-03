<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return to_route('users.all');
    }

    public function edit($id)
    {
        $user = User::find($id);

        // Check if user exists
        if (!$user) {
            return redirect()->route('users.index')->with('error', 'User not found');
        }

        if ($user->UserType) {
            return match ($user->UserType->key) {
                'undergraduate_student' => to_route('undergraduate.edit', ['id' => $id]),
                'graduate_student'      => to_route('graduate.edit', ['id' => $id]),
                'faculty'               => to_route('faculties.edit', ['id' => $id]),
                'staff'                 => to_route('staff.edit', ['id' => $id]),
                'library_staff'         => to_route('admins.edit', ['id' => $id]),
                default                 => abort(404, 'User type not supported'),
            };
        }

        // Fallback for non-student users
        return redirect()->route('users.index')->with('error', 'User type not found');
    }

    public function all(Request $request)
    {
        return Inertia::render('users/Index');
    }

    public function fetchAll(Request $request)
    {
        $query = User::with('userType'); // 👈 load the relation

        // Exclude super admin users
        $query->whereHas('userType', function ($q) {
            $q->where('key', '!=', 'super_admin');
        });

        // Handle search
        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('library_id', 'like', "%{$searchTerm}%")
                    ->orWhere('card_number', 'like', "%{$searchTerm}%")
                    ->orWhere('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%");
            });
        }

        // Handle sorting
        if ($request->has('sort_field')) {
            $sortField = $request->get('sort_field');
            $sortDirection = $request->get('sort_direction', 'asc');

            // Handle user_type sorting with join
            if ($sortField === 'user_type') {
                $query->join('user_types', 'users.user_type_id', '=', 'user_types.id')
                    ->orderBy('user_types.name', $sortDirection)
                    ->select('users.*'); // Make sure to only select users columns
            } else {
                $query->orderBy($sortField, $sortDirection);
            }
        } else {
            $query->latest();
        }

        // Handle pagination
        $perPage = $request->get('per_page', 10);
        return $query->paginate($perPage);
    }

    public function getUserStatistics()
    {
        $totalPatrons = User::count();
        $undergraduateStudents = User::whereHas('userType', fn($q) => $q->where('key', 'undergraduate_student'))->count();
        $graduateStudents = User::whereHas('userType', fn($q) => $q->where('key', 'graduate_student'))->count();
        $allFaculty = User::whereHas('userType', fn($q) => $q->where('key', 'faculty'))->count();
        $allStaff = User::whereHas('userType', fn($q) => $q->where('key', 'staff'))->count();
        $allLibraryStaff = User::whereHas('userType', fn($q) => $q->where('key', 'library_staff'))->count();

        return response()->json([
            'total_patrons' => $totalPatrons,
            'undergraduate_students' => $undergraduateStudents,
            'graduate_students' => $graduateStudents,
            'all_faculty' => $allFaculty,
            'all_staff' => $allStaff,
            'all_library_staff' => $allLibraryStaff,
        ]);
    }

    public function import()
    {
        return Inertia::render('users/Import');
    }

    public function importStore(Request $request)
    {
        $success_message = "";

        try {

            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max, 10240
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Please fix the validation errors below.');
            throw $e; // Re-throw to let Laravel handle the redirect with errors
        }

        try {

            $csv_path = $request->csv_file->store('temp/csv_imports');

            if ($csv_path && Storage::exists($csv_path)) {

                $csv_content = Storage::get($csv_path);
                $csv_data = array_map('str_getcsv', explode("\n", $csv_content));

                // Remove header row if it exists
                $headers = array_shift($csv_data);

                $failed_count = 0;
                $errors = [];
                $imported_count = 0;

                foreach ($csv_data as $row_index => $row) {
                    try {
                        // Trim all values in the row and check for emptiness
                        $row = array_map('trim', $row); // Trim all values ONCE
                        if (empty(array_filter($row, fn($value) => $value !== '' && $value !== null))) {
                            \Log::warning('Skipping empty row ' . ($row_index + 1) . ':', $row);
                            continue;
                        }

                        $library_id = null;
                        if (!empty($row[0]) && is_numeric($row[0]) && (int)$row[0] !== 0) {
                            $library_id = (int)$row[0];
                        }

                        $card_number = null;
                        if (!empty($row[1]) && is_numeric($row[1]) && (int)$row[1] !== 0) {
                            $card_number = (int)$row[1];
                        }

                        $school_id = null;
                        if (!empty($row[2]) && is_numeric($row[2]) && (int)$row[2] !== 0) {
                            $school_id = (int)$row[2];
                        }

                        $first_name = null;
                        if (!empty($row[3]) && is_string($row[3]) && $row[3] !== '') {
                            $first_name = $row[3]; // Already trimmed
                        }

                        $middle_initial = null;
                        if (!empty($row[4]) && is_string($row[4])) {
                            $clean = preg_replace('/[^\p{L}]/u', '', $row[4]); // Already trimmed
                            if (strlen($clean) >= 1) {
                                $middle_initial = strtoupper($clean[0]);
                            }
                        }

                        $last_name = null;
                        if (!empty($row[5]) && is_string($row[5]) && $row[5] !== '') {
                            $last_name = $row[5]; // Already trimmed
                        }

                        $sex = null;
                        if (!empty($row[6]) && is_string($row[6]) && $row[6] !== '') {
                            if (strtolower($row[6]) === 'female') { // Already trimmed
                                $sex = 'F';
                            } elseif (strtolower($row[6]) === 'male') {
                                $sex = 'M';
                            }
                        }

                        $contact_number = null;
                        if (!empty($row[7]) && is_string($row[7]) && $row[7] !== '') {
                            $digits_only = preg_replace('/\D/', '', $row[7]); // Already trimmed
                            if (strlen($digits_only) === 10) {
                                $contact_number = $digits_only;
                            }
                        }

                        $email = null;
                        if (!empty($row[8]) && is_string($row[8]) && $row[8] !== '') {
                            if (filter_var($row[8], FILTER_VALIDATE_EMAIL)) {
                                $email = $row[8];
                            }
                        }

                        $user_profile = null;
                        if (!empty($row[10]) && is_string($row[10]) && $row[10] !== '') {
                            $user_profile = $row[10];
                        }

                        $user_type_id = null;
                        $student_type = null;
                        if (!empty($row[11]) && is_string($row[11]) && $row[11] !== '') {
                            $user_type = ucwords($row[11]);
                            if($user_type === 'UndergraduateStudent' || $user_type === 'GraduateStudent') {
                                if ($user_type === 'UndergraduateStudent') {
                                    $student_type = 'UndergraduateStudent';
                                } else {
                                    $student_type = 'GraduateStudent';
                                }
                                $user_type = 'GraduateStudent';
                            }
                            $user_type_from_db = UserType::where('name', $user_type)->first();
                            if ($user_type_from_db) {
                                $user_type_id = $user_type_from_db->id;
                            } else {
                                // Generate key from name
                                $key = strtolower(str_replace(' ', '_', $user_type));
                                $user_type_id = UserType::create([
                                    'key' => $key,
                                    'name' => $user_type,
                                ])->id;
                            }
                        }

                        $user_data = [
                            'library_id' => $library_id,
                            'card_number' => $card_number,
                            'school_id' => $school_id,
                            'first_name' => $first_name,
                            'middle_initial' => $middle_initial,
                            'last_name' => $last_name,
                            'sex' => $sex,
                            'contact_number' => $contact_number,
                            'email' => $email,
                            'profile_image' => $user_profile,
                            'user_type_id' => $user_type_id,
                        ];

                        $student_id = null;
                        if ($library_id) {
                            if (preg_match('/^\d{9}$/', $library_id)) {
                                $student_id = $library_id;
                            }
                        }

                        $student_data = [
                            'student_type' => $student_type,
                            'student_id' => $student_id,
                        ];

                        $user = User::create($user_data);

                        if ($student_type) {
                            $user->student()->create($student_data);
                        }

                        $imported_count++;

                        $success_message = "Import completed! { $imported_count } book(s) imported successfully.";
                        if ($failed_count > 0) {
                            $success_message .= " {$failed_count} row(s) failed.";
                        }

                        if (!empty($errors)) {
                            session()->flash('import_errors', $errors);
                        } else {
                            session()->flash('success', $success_message);
                        }

                    } catch (\Exception $e) {
                        $failed_count++;
                        $errors[] = "Row " . ($row_index + 1) . ": " . $e->getMessage();
                        Log::info('Processing row', ['row_index' => $row_index + 1, 'data' => $row]);
                        foreach ($errors as $error) {
                            Log::error($error);
                            if ($failed_count > 5) {
                                if (app()->environment('local', 'development')) {
                                    dd('Too many errors');
                                }
                                // Optionally throw exception or handle differently in production
                                throw new \Exception("Too many errors occurred during processing");
                            }
                        }
                    }
                }
            }

            Storage::delete($csv_path);

        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database-specific errors
            $message = app()->environment('production')
                ? 'Failed to add record. Please try again.'
                : 'Failed to add record: ' . $e->getMessage();
            session()->flash('error', $message);
        } catch (\Exception $e) {
            // Handle any other unexpected errors
            $message = app()->environment('production')
                ? 'An unexpected error occurred. Please try again.'
                : 'An unexpected error occurred: ' . $e->getMessage();
            session()->flash('error', $message);
        }

        return to_route('users.import')->with('success', $success_message);

    }
}
