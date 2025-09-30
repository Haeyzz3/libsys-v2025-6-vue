<?php

namespace App\Console\Commands;

use App\Models\LibrarySetting;
use App\Models\LibraryVisit;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoLogoutLibraryVisits extends Command
{
    // Added --since-days option. Kept --dry-run and --force for behavior control.
    protected $signature = 'library:auto-logout '
        . '{--dry-run : Show what would be updated without persisting} '
        . '{--force : Also close today\'s still-open visits even if current time is before today\'s closing time} '
        . '{--since-days=7 : Only consider visits whose entry date is within the past N days (inclusive)}';

    protected $description = 'Auto set exit_time for still-open student visits (undergrad/grad) whose visit date is past closing (or today past closing / forced), marking them as auto logged out.';

    public function handle(): int
    {
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();

        $sinceDays = (int) $this->option('since-days');
        if ($sinceDays < 0) {
            $sinceDays = 0; // clamp
        }
        $thresholdDate = $now->copy()->subDays($sinceDays)->startOfDay();

        // Load operating hours from settings (expected structure: lowercase weekday => ['open' => HH:MM, 'close' => HH:MM])
        $operating = LibrarySetting::getValue('operating_hours', []);

        // Helper to get closing Carbon for a given date
        $getClosingForDate = function (Carbon $date) use ($operating): Carbon {
            $dayKey = strtolower($date->format('l')); // e.g. monday
            $closeStr = $operating[$dayKey]['close'] ?? '22:00';
            if (!preg_match('/^\d{1,2}:\d{2}$/', $closeStr)) {
                $closeStr = '22:00';
            }
            // Build full timestamp
            return Carbon::parse($date->format('Y-m-d') . ' ' . $closeStr . ':00');
        };

        // Precalculate today closing for same-day evaluation
        $todayClosing = $getClosingForDate($todayStart);

        $this->info('Scanning for open student visits...');
        $this->line('Current time: ' . $now->toDateTimeString());
        $this->line('Today closing: ' . $todayClosing->toDateTimeString());
        $this->line('Since days window: ' . $sinceDays . ' (threshold date: ' . $thresholdDate->toDateString() . ')');

        // Query open visits for student types (3,4) within since-days window
        $baseQuery = LibraryVisit::query()
            ->whereNull('exit_time')
            ->where('entry_time', '>=', $thresholdDate)
            ->whereHas('user', function ($q) {
                $q->whereIn('user_type_id', [3, 4]); // undergrad & grad IDs
            });

        $totalOpen = (clone $baseQuery)->count();
        if ($totalOpen === 0) {
            $this->info('No open student visits found in window.');
            return self::SUCCESS;
        }
        $this->info("Found {$totalOpen} open visit(s) to evaluate.");

        $eligibleIds = [];
        $perDateCounts = [];

        $baseQuery->orderBy('id')->chunkById(500, function ($visits) use ($now, $todayStart, $todayClosing, $getClosingForDate, &$eligibleIds, &$perDateCounts) {
            foreach ($visits as $visit) {
                $entryTime = $visit->entry_time instanceof Carbon ? $visit->entry_time : Carbon::parse($visit->entry_time);
                $visitDateStart = $entryTime->copy()->startOfDay();
                $closingForVisitDate = $getClosingForDate($visitDateStart);

                $isPastDay = $visitDateStart->lt($todayStart);
                $isSameDay = $visitDateStart->equalTo($todayStart);

                $eligible = false;
                if ($isPastDay) {
                    // Any previous day is automatically past its closing time.
                    $eligible = true;
                } elseif ($isSameDay) {
                    // Same-day: only if current time >= today closing OR will be forced (handled later with --force logic)
                    if ($now->greaterThanOrEqualTo($todayClosing)) {
                        $eligible = true;
                    }
                }

                // For today before closing but command forced, mark same-day visits eligible.
                // We evaluate force flag outside initial condition so we still separate logic clarity.
                if (!$eligible && $isSameDay && $this->option('force')) {
                    $eligible = true;
                }

                if ($eligible) {
                    $exitAt = $closingForVisitDate->copy()->addSecond();
                    // Guard: if exit_time somehow concurrently set, skip
                    if ($visit->exit_time === null) {
                        $eligibleIds[] = [
                            'id' => $visit->id,
                            'exit_time' => $exitAt,
                        ];
                        $dateKey = $visitDateStart->toDateString();
                        $perDateCounts[$dateKey] = ($perDateCounts[$dateKey] ?? 0) + 1;
                    }
                }
            }
        });

        $eligibleCount = count($eligibleIds);
        if ($eligibleCount === 0) {
            $this->info('No eligible visits to auto logout at this time.');
            return self::SUCCESS;
        }

        $this->info("Eligible visits to update: {$eligibleCount}");
        foreach ($perDateCounts as $date => $cnt) {
            $this->line("  - {$date}: {$cnt}");
        }

        if ($this->option('dry-run')) {
            $sample = collect($eligibleIds)->take(10)->pluck('id')->implode(', ');
            $this->line('Dry run mode: first up to 10 IDs => ' . $sample);
            return self::SUCCESS;
        }

        // Perform updates (per-row because exit_time differs by date)
        $updated = 0;
        foreach (array_chunk($eligibleIds, 500) as $chunk) {
            foreach ($chunk as $row) {
                $affected = LibraryVisit::where('id', $row['id'])
                    ->whereNull('exit_time') // safety check
                    ->update([
                        'exit_time' => $row['exit_time'],
                        'auto_logged_out' => true,
                        'violation_type' => 'AUTO_AFTER_HOURS',
                        'updated_at' => Carbon::now(),
                    ]);
                $updated += $affected;
            }
        }

        $this->info("Auto logged out {$updated} visit(s).");
        return self::SUCCESS;
    }
}
