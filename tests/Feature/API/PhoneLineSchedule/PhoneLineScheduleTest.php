<?php

namespace Tests\Feature\API\PhoneLineSchedule;

use App\Models\Competition;
use App\Models\Organisation;
use App\Models\PhoneBookEntry;
use App\Models\PhoneLineSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PhoneLineScheduleTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->login();

        $this->organisation = Organisation::factory()->create();
    }

    public function test_can_get_specific_schedule()
    {
        PhoneBookEntry::factory()->create(['phone_number' => '441234567897']);
        PhoneBookEntry::factory()->create(['phone_number' => '445555555555']);

        $scheduleA = PhoneLineSchedule::factory()->create(['competition_phone_number' => '441234567897', 'action_at' => '2025-09-09 09:00:00']);
        $scheduleB = PhoneLineSchedule::factory()->create(['competition_phone_number' => '445555555555', 'action_at' => '2025-09-09 09:00:00']);

        $this->getJson(route('phone-number-schedule.show', $scheduleA))
            ->assertOk()
            ->assertSee($scheduleA->competition_phone_number)
            ->assertDontSee($scheduleB->competition_phone_number);
    }

    public function test_can_get_all_schedules()
    {
        PhoneBookEntry::factory()->create(['phone_number' => '441234567897']);
        PhoneBookEntry::factory()->create(['phone_number' => '445555555555']);

        $scheduleA = PhoneLineSchedule::factory()->create(['competition_phone_number' => '441234567897', 'action_at' => '2025-09-09 09:00:00']);
        $scheduleB = PhoneLineSchedule::factory()->create(['competition_phone_number' => '445555555555', 'action_at' => '2025-09-09 09:00:00']);

        $this->postJson(route('phone-number-schedule.index'))
            ->assertOk()
            ->assertSee($scheduleA->competition_phone_number)
            ->assertSee($scheduleB->competition_phone_number);
    }

    public function test_can_get_all_schedules_relating_to_a_number()
    {
        PhoneBookEntry::factory()->create(['phone_number' => '441234567897']);
        PhoneBookEntry::factory()->create(['phone_number' => '445555555555']);

        $scheduleA = PhoneLineSchedule::factory()->create(['competition_phone_number' => '441234567897', 'action_at' => '2025-09-09 09:00:00']);
        $scheduleB = PhoneLineSchedule::factory()->create(['competition_phone_number' => '441234567897', 'action_at' => '2025-10-09 09:00:00']);
        $scheduleC = PhoneLineSchedule::factory()->create(['competition_phone_number' => '445555555555', 'action_at' => '2025-09-09 09:00:00']);

        $this->postJson(route('phone-number-schedule.index'), [
            'competition_phone_number' => '441234567897'
        ])
            ->assertOk()
            ->assertSee($scheduleA->competition_phone_number)
            ->assertSee($scheduleB->competition_phone_number)
            ->assertDontSee($scheduleC->competition_phone_number);
    }

    public function test_can_create_a_schedule()
    {
        $competition = Competition::factory()->create();

        PhoneBookEntry::factory()->create(['phone_number' => '441234567897']);

        $this->postJson(route('phone-number-schedule.create'), [
            'organisation_id' => $this->organisation->id,
            'competition_id' => $competition->id,
            'competition_phone_number' => '441234567897',
            'action_at' => now()->addMinute()->format('Y-m-d\TH:i:s\Z'),
        ])
            ->assertCreated();
    }

    public function test_cant_create_a_schedule_with_an_unknown_number()
    {
        $competition = Competition::factory()->create();

        $this->postJson(route('phone-number-schedule.create'), [
            'competition_id' => $competition->id,
            'competition_phone_number' => '441234567897',
            'action_at' => now()->addMinute()->format('Y-m-d\TH:i:s\Z'),
        ])
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->has('message')
                    ->where('data.0.source', 'organisation_id')
                    ->where('data.1.source', 'competition_phone_number');
            });
    }

    public function test_cant_create_a_schedule_whereby_a_number_and_action_at_already_exist()
    {


        $competition = Competition::factory()->create();

        PhoneBookEntry::factory()->create(['phone_number' => '441234567897']);

        PhoneLineSchedule::factory()->create(['competition_phone_number' => '441234567897', 'action_at' => now()->addMinute()]);

        $this->postJson(route('phone-number-schedule.create'), [
            'competition_id' => $competition->id,
            'competition_phone_number' => '441234567897',
            'action_at' => now()->addMinute()->format('Y-m-d\TH:i:s\Z'),
        ])
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->has('message')
                    ->where('data.0.source', 'competition_phone_number')
                    ->where('data.0.message', 'A schedule for this phone number already exists at the specified action time.');
            });
    }

    public function test_cant_update_a_schedule()
    {
        Carbon::setTestNow('2025-09-09 08:00:00');



        $competition = Competition::factory()->create();

        PhoneBookEntry::factory()->create(['phone_number' => '441234567897']);

        $schedule = PhoneLineSchedule::factory()->create(['competition_phone_number' => '441234567897', 'action_at' => '2025-09-09 09:00:00']);

        $this->postJson(route('phone-number-schedule.update', $schedule->id), [
            'competition_id' => $competition->id,
            'competition_phone_number' => '441234567897',
            'action_at' => '2025-09-09T09:00:00Z',
        ])
            ->assertOk();
    }

    public function test_validation()
    {


        $this->postJson(route('phone-number-schedule.create'))
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->has('message')
                    ->where('data.0.source', 'organisation_id')
                    ->where('data.1.source', 'competition_id')
                    ->where('data.2.source', 'competition_phone_number')
                    ->where('data.3.source', 'action_at');
            });
    }

    public function test_can_delete_a_schedule()
    {


        $schedule = PhoneLineSchedule::factory()->create(['processed' => '0']);

        $this->deleteJson(route('phone-number-schedule.delete', $schedule->id))
            ->assertNoContent();
    }

    public function test_cant_delete_a_schedule_when_its_already_been_processed()
    {


        $schedule = PhoneLineSchedule::factory()->create(['processed' => '1']);

        $this->deleteJson(route('phone-number-schedule.delete', $schedule->id))
            ->assertStatus(417);
    }

    public function test_cant_delete_a_non_existent_schedule()
    {


        $this->deleteJson(route('phone-number-schedule.delete', 22))
            ->assertNotFound();
    }

}
