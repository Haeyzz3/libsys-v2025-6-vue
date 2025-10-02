<?php

namespace App\Console\Commands;

use App\Models\College;
use App\Models\Course;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateStudentDataFromCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-student-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update or create student users from the new CSV data, including parsing names and generating emails.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting student data update process...");

        $csvPath = public_path('storage/csv_for_seeding/updated_students.csv'); // <-- IMPORTANT: Change to your new CSV file name
        if (!file_exists($csvPath)) {
            $this->error("CSV file not found at: {$csvPath}");
            return 1;
        }

        // Read the file with UTF-8 encoding
        $fileContents = file_get_contents($csvPath);
        $fileContents = mb_convert_encoding($fileContents, 'UTF-8', 'auto');
        $csvData = array_map('str_getcsv', explode("\n", $fileContents));

        // $csvData = array_map('str_getcsv', file($csvPath));
        $header = array_shift($csvData); // Remove header

        // Pre-fetch user type ID to avoid querying in a loop
        $studentUserTypeId = UserType::where('key', 'undergraduate_student')->firstOrFail()->id;

        $progressBar = $this->output->createProgressBar(count($csvData));
        $progressBar->start();

        foreach ($csvData as $row) {
            DB::beginTransaction();
            try {
                // Assuming CSV columns: library_id, student_name, sex, college, course
                $libraryIdStr = trim($row[0]);
                $fullName = trim($row[1]);
                $sex = trim($row[2]);
                $collegeCode = trim($row[3]);
                $courseCode = trim($row[4]);

                // 1. --- Parse Full Name ---
                list($lastName, $firstNamePart) = array_map('trim', explode(',', $fullName, 2));
                $lastName = ucwords(strtolower($lastName)); // "DELA CRUZ" -> "Dela Cruz"

                $middleInitial = null;
                if (str_contains($firstNamePart, '.')) {
                    $lastSpacePos = strrpos($firstNamePart, ' ');
                    $firstName = trim(substr($firstNamePart, 0, $lastSpacePos));
                    $middleInitialPart = trim(str_replace('.', '', substr($firstNamePart, $lastSpacePos)));
                    $middleInitial = substr($middleInitialPart, 0, 1); // Take only the first character
                } else {
                    $firstName = trim($firstNamePart);
                }

                // 2. --- Generate Email ---
                // Get only the part of the library_id after the hyphen
                $libraryIdParts = explode('-', $libraryIdStr);
                $numericLibId = count($libraryIdParts) > 1 ? $libraryIdParts[1] : preg_replace('/[^0-9]/', '', $libraryIdStr); // Fallback if no hyphen

                // Get the first letter of each word in the first name
                $firstNameWords = explode(' ', $firstName);
                $emailFirstNamePart = '';
                foreach ($firstNameWords as $word) {
                    if (!empty($word)) {
                        $emailFirstNamePart .= strtolower(substr($word, 0, 1));
                    }
                }

                $emailMiddleInitial = $middleInitial ? strtolower($middleInitial) : '';
                $emailLastName = strtolower(str_replace(' ', '', $lastName));
                $email = "{$emailFirstNamePart}{$emailMiddleInitial}{$emailLastName}{$numericLibId}@usep.edu.ph";

                // 3. --- Find or Create User ---
                $user = User::updateOrCreate(
                    ['email' => $email], // Match user by email to prevent duplicates
                    [
                        'library_id' => $numericLibId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'middle_initial' => $middleInitial,
                        'email' => $email,
                        'sex' => strtolower(substr($sex, 0, 1)), // 'Male' -> 'm'
                        'user_type_id' => $studentUserTypeId,
                    ]
                );

                // 4. --- Look up College and Course IDs ---
                $college = College::where('code', $collegeCode)->first();
                if (!$college) {
                    throw new \Exception("College not found: '{$collegeCode}' for library ID {$libraryIdStr}");
                }

                $course = Course::where('code', $courseCode)->where('college_id', $college->id)->first();
                if (!$course) {
                    throw new \Exception("Course not found: '{$courseCode}' in college '{$collegeCode}'");
                }

                // 5. --- Update or Create Student Profile ---
                // Assuming you have an 'undergraduateStudent' relationship on your User model
                // This will link the user to their college and course
                $user->undergraduateStudent()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'college_id' => $college->id,
                        'course_id' => $course->id,
                        'major_id' => null, // Neglecting major as requested
                    ]
                );

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to process row for library ID {$libraryIdStr}: " . $e->getMessage());
                $this->warn("\nSkipping row for library ID {$libraryIdStr} due to error: " . $e->getMessage());
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info("\nStudent data update process completed successfully!");
        return 0;
    }
}
