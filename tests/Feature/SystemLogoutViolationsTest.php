<?php

namespace Tests\Feature;

use App\Models\LibraryVisit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemLogoutViolationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_logout_after_closing_detected()
    {
        Carbon::setTestNow(Carbon::create(2025, 9, 29, 21, 0, 0)); // Monday 9 PM

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

        DB::table('user_types')->updateOrInsert(
            ['id' => 3, 'key' => 'undergraduate'],
            [
                'name' => 'Undergraduate',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $staff = User::factory()->create();
        $this->actingAs($staff);

        $student = User::factory()->create([
            'library_id' => 123456,
            'user_type_id' => 3,
            'first_name' => 'Late',
            'last_name' => 'Student',
        ]);

        LibraryVisit::create([
            'user_id' => $student->id,
            'entry_time' => Carbon::parse('2025-09-29 19:30:00'),
            'exit_time' => Carbon::parse('2025-09-29 22:00:00'), // exactly closing (not violation)
        ]);

        LibraryVisit::create([
            'user_id' => $student->id,
            'entry_time' => Carbon::now()->subHours(5),
            'exit_time' => Carbon::parse('2025-09-29 21:30:00'), // before closing
        ]);

        LibraryVisit::create([
            'user_id' => $student->id,
            'entry_time' => Carbon::now()->subHours(3),
            'exit_time' => Carbon::parse('2025-09-29 22:30:00'), // after closing -> violation
        ]);

        $response = $this->getJson(route('logger.api.systemLogoutViolations'));
        $response->assertStatus(200);

        $data = $response->json('data.rows');
        $this->assertCount(1, $data, 'Only the after-hours visit should be returned.');
        $this->assertEquals(123456, $data[0]['studentId']);
        $this->assertEquals('Auto Logout After Hours', $data[0]['violation']);
    }
}
