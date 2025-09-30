<?php

namespace Tests\Feature;

use App\Models\LibraryVisit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutoLogoutCommandTest extends TestCase
{
    use RefreshDatabase;

    private function seedOperatingHours(): void
    {
        DB::table('library_settings')->updateOrInsert(
            ['key' => 'operating_hours'],
            [
                'value' => json_encode([
                    'monday' => ['open' => '08:00', 'close' => '22:00'],
                    'tuesday' => ['open' => '08:00', 'close' => '22:00'],
                    'wednesday' => ['open' => '08:00', 'close' => '22:00'],
                    'thursday' => ['open' => '08:00', 'close' => '22:00'],
                    'friday' => ['open' => '08:00', 'close' => '22:00'],
                    'saturday' => ['open' => '09:00', 'close' => '18:00'],
                    'sunday' => ['open' => '10:00', 'close' => '18:00'],
                ]),
                'description' => 'Test hours',
                'type' => 'json',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('library_settings')->updateOrInsert(
            ['key' => 'auto_logout_enabled'],
            [
                'value' => 'true',
                'description' => 'Enable auto logout (test)',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('user_types')->updateOrInsert(
            ['id' => 3, 'key' => 'undergraduate'],
            [
                'name' => 'Undergraduate',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function test_no_action_before_closing_time()
    {
        $this->seedOperatingHours();
        Carbon::setTestNow(Carbon::create(2025, 9, 29, 21, 30, 0)); // Monday 21:30, closing 22:00

        $student = User::factory()->create([
            'user_type_id' => 3,
            'library_id' => 111111,
        ]);

        $visit = LibraryVisit::create([
            'user_id' => $student->id,
            'entry_time' => Carbon::now()->subHours(1),
        ]);

        $this->artisan('library:auto-logout')
            ->expectsOutputToContain('Current time not past closing')
            ->assertExitCode(0);

        $this->assertNull($visit->fresh()->exit_time, 'Exit time should not be set before closing.');
    }

    public function test_auto_logout_after_closing_sets_exit_time_to_closing()
    {
        $this->seedOperatingHours();
        Carbon::setTestNow(Carbon::create(2025, 9, 29, 22, 30, 0)); // Monday 22:30 (after closing)

        $student = User::factory()->create([
            'user_type_id' => 3,
            'library_id' => 222222,
        ]);

        $visit = LibraryVisit::create([
            'user_id' => $student->id,
            'entry_time' => Carbon::now()->subHours(4),
        ]);

        $this->artisan('library:auto-logout')
            ->expectsOutputToContain('Auto-logged out 1 visit(s)')
            ->assertExitCode(0);

        $updated = $visit->fresh();
        $this->assertNotNull($updated->exit_time, 'Exit time should be set after command.');
        $this->assertEquals('2025-09-29 22:00:00', Carbon::parse($updated->exit_time)->toDateTimeString(), 'Exit time should match closing time.');

        // Authenticate to access protected endpoint
        $this->actingAs($student);
        // Ensure not considered a violation (exit_time == closing not > closing)
        $response = $this->getJson(route('logger.api.systemLogoutViolations'));
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.rows'), 'Auto-logged out visit at exact closing should not be a violation.');
    }

    public function test_auto_logout_disabled_does_not_update()
    {
        $this->seedOperatingHours();
        DB::table('library_settings')->updateOrInsert(
            ['key' => 'auto_logout_enabled'],
            [
                'value' => 'false',
                'description' => 'Disable auto logout (test)',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Carbon::setTestNow(Carbon::create(2025, 9, 29, 23, 0, 0)); // After closing

        $student = User::factory()->create([
            'user_type_id' => 3,
            'library_id' => 333333,
        ]);

        $visit = LibraryVisit::create([
            'user_id' => $student->id,
            'entry_time' => Carbon::now()->subHours(2),
        ]);

        $this->artisan('library:auto-logout')
            ->expectsOutputToContain('Auto logout disabled')
            ->assertExitCode(0);

        $this->assertNull($visit->fresh()->exit_time, 'Exit time should remain null when disabled.');
    }
}

