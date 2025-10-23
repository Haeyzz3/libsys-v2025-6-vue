<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class StudentDataUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Using your new CSV file path
        $csvPath = public_path('storage/csv_for_seeding/updated_student_masterlist.csv');

        try {
            $csvData = $this->loadCsvData($csvPath);
            $userTypeIds = $this->getUserTypeIds();
            $this->processCsvRows($csvData, $userTypeIds);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Load and parse CSV data.
     */
    private function loadCsvData(string $csvPath): array
    {
        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            throw new \Exception("CSV file not found");
        }

        // Read the entire file content as raw bytes
        $csvContent = file_get_contents($csvPath);

        // --- START FIX: FILE ENCODING ---
        // This detects if the string is valid UTF-8.
        // If not, it assumes the file is ISO-8859-1 (a common default)
        // and converts it to UTF-8, which fixes characters like 'ñ'.
        if (!mb_check_encoding($csvContent, 'UTF-8')) {
            $this->command->warn("File encoding issue detected. Attempting to convert from ISO-8859-1 to UTF-8...");
            $csvContent = mb_convert_encoding($csvContent, 'UTF-8', 'ISO-8859-1');
        }
        // --- END FIX ---

        // --- START FIX: LINE ENDINGS ---
        // This is more robust than explode("\n", ...)
        // It handles all line ending types (\n, \r\n, \r)
        // and skips empty lines, which often cause errors.
        $lines = preg_split('/(\r\n|\r|\n)/', $csvContent, -1, PREG_SPLIT_NO_EMPTY);
        if ($lines === false) {
            throw new \Exception("Failed to split CSV content.");
        }
        // --- END FIX ---

        $csvData = array_map('str_getcsv', $lines);
        array_shift($csvData); // Remove header row

        return $csvData;
    }

    /**
     * Retrieve user type IDs from the database.
     */
    private function getUserTypeIds(): array
    {
        return [
            'undergraduate_student' => UserType::where('key', 'undergraduate_student')->firstOrFail()->id,
            'graduate_student' => UserType::where('key', 'graduate_student')->firstOrFail()->id,
        ];
    }

    /**
     * Process CSV rows and import/update users.
     */
    private function processCsvRows(array $csvData, array $userTypeIds): void
    {
        $importedCount = 0;
        $failedCount = 0;
        $errors = [];
        $progressBar = $this->command->getOutput()->createProgressBar(count($csvData));
        $progressBar->start();

        foreach ($csvData as $rowIndex => $row) {
            try {
                $row = array_map('trim', $row);
                if ($this->isRowEmpty($row)) {
                    Log::warning('Skipping empty row ' . ($rowIndex + 1));
                    continue;
                }

                // Assumed CSV column indexes
                // 0: year_level
                // 1: library_id
                // 2: student_full_name
                // 3: sex
                // 4: college_code
                // 5: course_code

                $libraryId = $this->parseLibraryId($row[1] ?? null);
                if (!$libraryId) {
                    throw new \Exception("Skipping row: Invalid or missing library_id.");
                }

                // 1. Solve Name Parsing
                $nameParts = $this->parseFullName($row[2] ?? '');
                if (!$nameParts) {
                    throw new \Exception("Skipping row: Could not parse name '{$row[2]}'.");
                }

                // 2. Solve Email Generation
                $email = $this->generateUserEmail($nameParts, $libraryId);

                // 3. Find College, Course, and UserType
                $collegeCode = trim($row[4] ?? '');
                $courseCode = trim($row[5] ?? '');

                // 3a. Find College by its 'code'
                $college = College::where('code', $collegeCode)->first();

                if (!$college) {
                    Log::warning("Row " . ($rowIndex + 1) . ": College code '{$collegeCode}' not found. Skipping.");
                    $failedCount++;
                    $errors[] = "Row " . ($rowIndex + 1) . ": College code '{$collegeCode}' not found.";
                    continue;
                }

                // Use the 'college_type' from the database ('undergraduate' or 'graduate')
                if (!in_array($college->college_type, ['undergraduate', 'graduate'])) {
                    Log::warning("Row " . ($rowIndex + 1) . ": College '{$collegeCode}' has invalid type '{$college->college_type}'. Skipping.");
                    continue;
                }

                // --- START FIX ---
                // Map the college_type to the correct UserType key
                $userTypeKey = ($college->college_type === 'undergraduate')
                    ? 'undergraduate_student'
                    : 'graduate_student';

                // Use the correct key to get the ID
                $userTypeId = $userTypeIds[$userTypeKey];
                // --- END FIX ---

                // 3b. Find Course by its 'code' and relation to the college
                $course = $college->courses()->where('code', $courseCode)->first();

                if (!$course) {
                    Log::warning("Row " . ($rowIndex + 1) . ": Course code '{$courseCode}' not found for college '{$collegeCode}'. Course ID will be null.");
                }

                // 4. Update or Create the User
                $userData = [
                    'first_name' => $nameParts['first_name'],
                    'middle_initial' => $nameParts['middle_initial'],
                    'last_name' => $nameParts['last_name'],
                    'email' => $email,
                    'sex' => $this->parseSex($row[3] ?? null),
                    'user_type_id' => $userTypeId,
                ];

                // Check if a user with this email already exists (but different library_id)
                $existingUserWithEmail = User::where('email', $email)
                    ->where('library_id', '!=', $libraryId)
                    ->first();

                if ($existingUserWithEmail) {
                    // If email exists for a different user, append library_id to make it unique
                    $userData['email'] = $this->generateUniqueEmail($nameParts, $libraryId, $email);
                    Log::info("Email conflict resolved for library_id {$libraryId}. Original: {$email}, New: {$userData['email']}");
                }

                $user = User::updateOrCreate(
                    ['library_id' => $libraryId], // Key to find user
                    $userData                     // Data to update or create
                );

                // 5. Update or Create the Student Profile (with year_level)
                $studentProfileData = [
                    'college_id' => $college->id,
                    'course_id' => $course ? $course->id : null,
                    'major_id' => null, // Per your request
                    'year_level' => $this->parseYearLevel($row[0] ?? null), // Add the new data
                ];

                // Use updateOrCreate on the relationship
                if ($user->user_type_id === $userTypeIds['undergraduate_student']) {
                    $user->undergraduateStudent()->updateOrCreate(['user_id' => $user->id], $studentProfileData);
                } elseif ($user->user_type_id === $userTypeIds['graduate_student']) {
                    $user->graduateStudent()->updateOrCreate(['user_id' => $user->id], $studentProfileData);
                }

                $importedCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Row " . ($rowIndex + 1) . ": " . $e->getMessage();
                Log::error("Processing row " . ($rowIndex + 1) . ": " . $e->getMessage(), ['data' => $row]);
            }
            $progressBar->advance();
        }

        $this->displayResults($progressBar, $importedCount, $failedCount, $errors);
    }

    /**
     * Parses a full name (e.g., "DELA CRUZ, Jon Yahzee J.") into parts.
     */
    private function parseFullName(string $fullName): ?array
    {
        if (empty(trim($fullName))) return null;

        // 1. Define and remove suffixes
        $suffixes = [' JR.', ' SR.', ' II', ' III', ' IV', ' V', ' JR', ' SR'];
        $cleanName = $fullName;
        foreach ($suffixes as $suffix) {
            $cleanName = str_ireplace($suffix, '', $cleanName);
        }

        // 2. Split by comma
        $parts = explode(',', $cleanName, 2);
        if (count($parts) !== 2) {
            Log::warning("Invalid name format: '{$fullName}'. Expected 'LAST, First M.'");
            return null;
        }

        $lastName = trim($parts[0]);
        $firstAndMiddle = trim($parts[1]);

        if (empty($lastName) || empty($firstAndMiddle)) {
            Log::warning("Invalid name parts: '{$fullName}'.");
            return null;
        }

        // 3. Split First and Middle, then find the initial
        $nameParts = preg_split('/\s+/u', $firstAndMiddle); // 'u' for unicode (Ñ, ñ)
        $lastPart = array_pop($nameParts);

        $firstName = '';
        $middleInitial = null;

        // Check if the last part is a middle initial (1-2 chars, maybe a period)
        if (preg_match('/^([A-ZÑa-zñ]{1,2})\.?$/u', $lastPart, $matches)) {
            $middleInitial = mb_strtoupper(mb_substr($matches[1], 0, 1, 'UTF-8'), 'UTF-8');
            $firstName = implode(' ', $nameParts);
        } else {
            // Not a middle initial, so it's part of the first name
            $firstName = implode(' ', $nameParts) . ' ' . $lastPart;
        }

        return [
            'first_name' => trim($firstName),
            'last_name' => trim($lastName),
            'middle_initial' => $middleInitial,
        ];
    }

    /**
     * Generates the specific university email format.
     */
    private function generateUserEmail(array $nameParts, string $libraryId): string
    {
        // "Jon Yahzee" -> "jy"
        $firstInitials = '';
        $firstNames = preg_split('/\s+/u', $nameParts['first_name']);
        foreach ($firstNames as $part) {
            if (!empty($part)) {
                $firstInitials .= mb_substr($part, 0, 1, 'UTF-8');
            }
        }

        // "J" -> "j" (or "" if null)
        $middle = $nameParts['middle_initial'] ?? '';

        // "DELA CRUZ" -> "delacruz"
        $last = preg_replace('/\s+/u', '', $nameParts['last_name']);

        // Handle both formats: "202300151" (no hyphen) or "2023-00151" (with hyphen)
        $idPart = '';
        if (strlen($libraryId) === 9) {
            // Format: "202300151" -> extract last 5 digits: "00151"
            $idPart = substr($libraryId, -5);
        } else {
            // Fallback: try to split by hyphen if it exists
            $idParts = explode('-', $libraryId);
            if (count($idParts) === 2) {
                $idPart = $idParts[1];
            }
        }

        $emailPrefix = $firstInitials . $middle . $last . $idPart;

        // Combine and convert to lowercase
        return mb_strtolower($emailPrefix, 'UTF-8') . '@usep.edu.ph';
    }

    /**
     * Generates a unique email when there's a conflict.
     */
    private function generateUniqueEmail(array $nameParts, string $libraryId, string $originalEmail): string
    {
        // Extract the prefix from the original email (before @usep.edu.ph)
        $emailPrefix = str_replace('@usep.edu.ph', '', $originalEmail);

        // Add the full library_id to make it unique
        $uniquePrefix = $emailPrefix . str_replace('-', '', $libraryId);

        return mb_strtolower($uniquePrefix, 'UTF-8') . '@usep.edu.ph';
    }

    /**
     * Parses the library_id string and removes hyphens for database storage.
     */
    private function parseLibraryId($value): ?string
    {
        if (empty($value) || !is_string($value)) return null;

        // Accept both formats: "2023-00151" or "202300151"
        if (preg_match('/^\d{4}-?\d{5}$/', $value)) {
            // Remove any hyphens to store in database format (202300151)
            return str_replace('-', '', $value);
        }
        return null;
    }

    /**
     * Parses the year_level.
     */
    private function parseYearLevel($value): ?string
    {
        return (!empty($value) && is_string($value)) ? trim($value) : null;
    }

    /**
     * Parse sex value.
     */
    private function parseSex($value): ?string
    {
        if (empty($value) || !is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        // Handle various formats
        if (in_array($value, ['female', 'f'])) {
            return 'f';
        } elseif (in_array($value, ['male', 'm'])) {
            return 'm';
        }

        // Log unrecognized values for debugging
        Log::warning("Unrecognized sex value: '{$value}'");
        return null;
    }

    /**
     * Check if a row is empty.
     */
    private function isRowEmpty(array $row): bool
    {
        return empty(array_filter($row, fn($value) => $value !== '' && $value !== null));
    }

    /**
     * Display import results.
     */
    private function displayResults($progressBar, int $importedCount, int $failedCount, array $errors): void
    {
        $progressBar->finish();
        $this->command->newLine();
        $this->command->info("Import completed! {$importedCount} student(s) imported or updated successfully.");

        if ($failedCount > 0) {
            $this->command->warn("{$failedCount} row(s) failed.");
            foreach ($errors as $error) {
                $this->command->error($error);
            }
        }
    }

    /**
     * Handle exceptions during seeding.
     */
    private function handleException(\Exception $e): void
    {
        $message = 'Seeding error: ' . $e->getMessage();
        $this->command->error($message);
        Log::error($message, ['exception' => $e]);
    }
}
