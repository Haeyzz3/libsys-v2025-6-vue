<?php

namespace App\Console\Commands;

use App\Models\LibrarySetting;
use App\Models\LibraryVisit;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoLogoutLibraryVisits extends Command
{
    protected $signature = 'library:auto-logout {--dry-run : Show what would be updated without persisting} {--force : Run even if current time is before closing time}';
    protected $description = 'Automatically set exit_time for student visits (undergrad/grad) still open after closing time and mark them as auto logged out.';

    public function handle(): int
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $dayKey = strtolower($now->format('l')); // e.g. monday, tuesday

        $operating = LibrarySetting::getValue('operating_hours', []);
        $closeStr = $operating[$dayKey]['close'] ?? '22:00'; // fallback to 10PM

        // Normalize close string (HH:MM or H:MM)
        if (!preg_match('/^\d{1,2}:\d{2}$/', $closeStr)) {
            $this->warn("Invalid close time '{$closeStr}' in settings; falling back to 22:00");
            $closeStr = '22:00';
        }

        $closingDateTime = Carbon::parse($today->format('Y-m-d') . ' ' . $closeStr . ':00');

        if ($now->lt($closingDateTime) && !$this->option('force')) {
            $this->info('Current time is before closing; nothing to do. Use --force to override.');
            return self::SUCCESS;
        }

        $autoExit = $closingDateTime->copy()->addSecond(); // ensure > closing time rule
        $this->info('Closing time: ' . $closingDateTime->toDateTimeString());
        $this->info('Auto logout timestamp to set: ' . $autoExit->toDateTimeString());

        // Build base query (joins introduce ambiguous id with chunkById, so we'll pluck first)
        $baseQuery = LibraryVisit::query()
            ->join('users', 'library_visits.user_id', '=', 'users.id')
            ->whereIn('users.user_type_id', [3,4]) // 3=undergrad,4=grad (based on existing usage)
            ->whereNull('library_visits.exit_time')
            ->whereDate('library_visits.entry_time', $today);

        $openIds = $baseQuery->pluck('library_visits.id');
        $countOpen = $openIds->count();

        if ($countOpen === 0) {
            $this->info('No open student visits to auto logout.');
            return self::SUCCESS;
        }

        $this->info("Found {$countOpen} open student visit(s) to auto logout.");

        if ($this->option('dry-run')) {
            $sample = $openIds->take(10)->implode(', ');
            $this->line('Dry run mode - first up to 10 IDs: ' . $sample);
            return self::SUCCESS;
        }

        $updated = 0;
        DB::transaction(function () use ($openIds, $autoExit, &$updated) {
            foreach ($openIds->chunk(500) as $chunk) {
                $affected = LibraryVisit::whereIn('id', $chunk->all())
                    ->update([
                        'exit_time' => $autoExit,
                        'auto_logged_out' => true,
                        'violation_type' => 'AUTO_AFTER_HOURS',
                        'updated_at' => Carbon::now(),
                    ]);
                $updated += $affected;
            }
        });

        $this->info("Auto logged out {$updated} visit(s).");
        return self::SUCCESS;
    }
}
