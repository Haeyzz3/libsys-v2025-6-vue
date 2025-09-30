<?php

namespace Tests\Feature;

use App\Models\LibrarySetting;
use App\Models\LibraryVisit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutoLogoutLibraryVisitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Run default migrations (RefreshDatabase trait handles this)
        // Seed a minimal set of user_types ensuring IDs 3 & 4 exist (undergrad/grad expectation in code)
        DB::table('user_types')->insert([
            ['id' => 1, 'key' => 'admin', 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'key' => 'staff', 'name' => 'Staff', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'key' => 'undergrad', 'name' => 'Undergraduate', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'key' => 'grad', 'name' => 'Graduate', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_auto_logout_command_marks_open_visits_and_endpoint_returns_them(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 10, 1, 21, 30)); // 9:30 PM (before closing 22:00)

        // Ensure operating hours present (migration inserts defaults, but we assert/override for clarity)
        LibrarySetting::updateOrCreate(
            ['key' => 'operating_hours'],
            [
                'type' => 'json',
                'value' => json_encode([
                    'wednesday' => ['open' => '08:00', 'close' => '22:00'], // 2025-10-01 is a Wednesday
                ]),
                'description' => 'Test operating hours'
            ]
        );

        // Auth user to hit endpoint later
        $authUser = User::factory()->create();

        // Student user (undergrad id=3)
        $student = User::factory()->create([
            'user_type_id' => 3,
            'library_id' => 123456,
        ]);

        // Create open visit earlier today (no exit_time)
        $entryTime = Carbon::now()->copy()->subHours(2); // 7:30 PM
        $visit = LibraryVisit::create([
            'user_id' => $student->id,
            'entry_time' => $entryTime,
            'entry_method' => 'manual',
        ]);

        // Sanity: still open
        $this->assertNull($visit->exit_time);

        // Run command with --force (since current time < closing)
        Artisan::call('library:auto-logout', ['--force' => true]);

        $visit->refresh();
        $this->assertNotNull($visit->exit_time, 'Exit time should be set by auto logout');
        $this->assertTrue($visit->auto_logged_out, 'auto_logged_out flag should be true');
        $this->assertEquals('AUTO_AFTER_HOURS', $visit->violation_type);

        // Expected exit time: closing (22:00) + 1s
        $expectedExit = Carbon::create(2025, 10, 1, 22, 0, 1);
        $this->assertTrue($visit->exit_time->equalTo($expectedExit), 'Exit time should be closing time + 1 second');

        // Now move test time after closing to mimic endpoint filtering period
        Carbon::setTestNow(Carbon::create(2025, 10, 1, 23, 0));

        $response = $this->actingAs($authUser)->getJson(route('logger.api.systemLogoutViolations', [
            'filter' => 'day'
        ]));

        $response->assertStatus(200)->assertJson(['success' => true]);
        $data = $response->json('data.rows');
        $this->assertNotEmpty($data, 'Should return at least one violation');
        $first = $data[0];
        $this->assertEquals($student->library_id, $first['studentId']);
        $this->assertEquals('Auto Logout After Hours', $first['violation']);
        $this->assertTrue($first['autoLoggedOut']);
    }

    public function test_manual_late_logout_is_not_listed_when_not_auto_flagged(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 10, 1, 23, 30)); // After closing

        // Ensure operating hours
        LibrarySetting::updateOrCreate(
            ['key' => 'operating_hours'],
            [
                'type' => 'json',
                'value' => json_encode([
                    'wednesday' => ['open' => '08:00', 'close' => '22:00'],
                ]),
                'description' => 'Test operating hours'
            ]
        );

        $authUser = User::factory()->create();
        $student = User::factory()->create([
            'user_type_id' => 3,
            'library_id' => 654321,
        ]);

        // Create a visit that already has a late manual logout AFTER closing but not auto flagged
        $entryTime = Carbon::create(2025, 10, 1, 21, 0); // 9 PM
        $logoutTime = Carbon::create(2025, 10, 1, 22, 5, 0); // 10:05 PM (> closing)
        LibraryVisit::create([
            'user_id' => $student->id,
            'entry_time' => $entryTime,
            'exit_time' => $logoutTime,
            'entry_method' => 'manual',
            'auto_logged_out' => false,
            'violation_type' => null,
        ]);

        $response = $this->actingAs($authUser)->getJson(route('logger.api.systemLogoutViolations', [
            'filter' => 'day'
        ]));
        $response->assertStatus(200)->assertJson(['success' => true]);
        $data = $response->json('data.rows');
        // Should be empty because only auto-logged-out violations are included
        $this->assertEmpty($data, 'Manual late logout should not appear in system logout violations list');
    }
}

