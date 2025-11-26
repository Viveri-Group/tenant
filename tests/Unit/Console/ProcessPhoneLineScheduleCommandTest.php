<?php

namespace Tests\Unit\Console;

use App\Models\ActiveCall;
use App\Models\ActiveCallOrphan;
use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\PhoneBookEntry;
use App\Models\PhoneLineSchedule;
use App\Models\PhoneLineScheduleAudit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProcessPhoneLineScheduleCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2004-04-30 09:10:00');

        $this->phoneBook = PhoneBookEntry::factory()->count(5)->create();

        $this->scheduledPhoneNumber = $this->phoneBook[0]->phone_number;

        $this->competitionOld = Competition::factory()->hasPhoneLines(1, ['phone_number' => $this->scheduledPhoneNumber])->create();

        $this->competitionNew = Competition::factory()->create();
    }

    public function test_single_phone_number_is_successfully_moved()
    {
        $schedule = PhoneLineSchedule::factory()->create([
            'competition_phone_number' => $this->scheduledPhoneNumber,
            'competition_id' => $this->competitionNew->id,
            'action_at' => '2004-04-30 09:10:00',
        ]);

        $this->artisan('viveri:process-phone-line-schedule');

        $schedule->refresh();

        $this->assertCount(1, $competitionPhoneLines = CompetitionPhoneLine::all());
        $phoneLine = $competitionPhoneLines->first();

        tap($phoneLine, function (CompetitionPhoneLine $phoneLine) use ($schedule) {
            $this->assertSame($this->scheduledPhoneNumber, $phoneLine->phone_number);
            $this->assertSame($phoneLine->competition_id, $this->competitionNew->id);
            $this->assertSame('2004-04-30 09:10:00', $schedule->action_at->format('Y-m-d H:i:s'));
            $this->assertSame('2004-04-30 09:10:00', $schedule->completed_at->format('Y-m-d H:i:s'));
            $this->assertSame($schedule->notes, "Successfully moved phone number: {$schedule->competition_phone_number} to competition {$schedule->competition_id}.");
            $this->assertTrue($schedule->success);
            $this->assertTrue($schedule->processed);
        });
    }

    public function test_multiple_phone_numbers_are_moved()
    {
        $competitionB = Competition::factory()->create();
        $competitionBScheduledNumber = $this->phoneBook[1]->phone_number;

        $scheduleA = PhoneLineSchedule::factory()->create([
            'competition_phone_number' => $this->scheduledPhoneNumber,
            'competition_id' => $this->competitionNew->id,
            'action_at' => '2004-04-30 09:10:00',
        ]);

        $scheduleB = PhoneLineSchedule::factory()->create([
            'competition_phone_number' => $competitionBScheduledNumber,
            'competition_id' => $competitionB->id,
            'action_at' => '2004-04-30 09:10:00',
        ]);

        $this->artisan('viveri:process-phone-line-schedule');

        $scheduleA->refresh();
        $scheduleB->refresh();

        $this->assertCount(2, $competitionPhoneLines = CompetitionPhoneLine::all());
        $phoneLineA = $competitionPhoneLines->get(0);
        $phoneLineB = $competitionPhoneLines->get(1);

        tap($phoneLineA, function (CompetitionPhoneLine $phoneLineA) use ($scheduleA) {
            $this->assertSame($this->scheduledPhoneNumber, $phoneLineA->phone_number);
            $this->assertSame($phoneLineA->competition_id, $this->competitionNew->id);
            $this->assertSame('2004-04-30 09:10:00', $scheduleA->action_at->format('Y-m-d H:i:s'));
            $this->assertTrue($scheduleA->processed);
            $this->assertSame('2004-04-30 09:10:00', $scheduleA->completed_at->format('Y-m-d H:i:s'));
            $this->assertSame($scheduleA->notes, "Successfully moved phone number: {$scheduleA->competition_phone_number} to competition {$scheduleA->competition_id}.");
            $this->assertTrue($scheduleA->success);
        });

        tap($phoneLineB, function (CompetitionPhoneLine $phoneLineB) use ($scheduleB, $competitionB, $competitionBScheduledNumber) {
            $this->assertSame($competitionBScheduledNumber, $phoneLineB->phone_number);
            $this->assertSame($phoneLineB->competition_id, $competitionB->id);
            $this->assertSame('2004-04-30 09:10:00', $scheduleB->action_at->format('Y-m-d H:i:s'));
            $this->assertTrue($scheduleB->processed);

            $this->assertSame('2004-04-30 09:10:00', $scheduleB->completed_at->format('Y-m-d H:i:s'));
            $this->assertSame($scheduleB->notes, "Successfully moved phone number: {$scheduleB->competition_phone_number} to competition {$scheduleB->competition_id}.");
            $this->assertTrue($scheduleB->success);
        });
    }

    public function test_phone_number_fails_to_be_moved_and_stops_before_making_any_changes()
    {
        $originalPhoneLine = $this->competitionOld->phoneLines()->first();

        $expectedCompPhoneLineDetails = [
            'id' => $originalPhoneLine->id,
            'competition_id' => $originalPhoneLine->competition_id,
            'phone_number' => $originalPhoneLine->phone_number,
        ];

        $schedule = PhoneLineSchedule::factory()->create([
            'competition_phone_number' => $this->scheduledPhoneNumber,
            'competition_id' => $this->competitionNew->id + 1,
            'action_at' => '2004-04-30 09:10:00',
        ]);

        $this->artisan('viveri:process-phone-line-schedule');

        $schedule->refresh();


        $this->assertCount(1, $compPhoneLines = CompetitionPhoneLine::all());

        tap($compPhoneLines->first(), function (CompetitionPhoneLine $competitionPhoneLine) use ($expectedCompPhoneLineDetails) {
            $this->assertSame($expectedCompPhoneLineDetails['id'], $competitionPhoneLine->id);
            $this->assertSame($expectedCompPhoneLineDetails['competition_id'], $competitionPhoneLine->competition_id);
            $this->assertSame($expectedCompPhoneLineDetails['phone_number'], $competitionPhoneLine->phone_number);
        });

        tap($schedule, function (PhoneLineSchedule $schedule) {
            $this->assertTrue($schedule->processed);
            $this->assertNotNull($schedule->completed_at);
            $this->assertSame($schedule->notes, "Schedule {$schedule->id} skipped: competition {$schedule->competition_id} does not exist.");
            $this->assertFalse($schedule->success);
        });
    }

    public function test_multiple_schedules_are_set_for_the_same_number()
    {
        $competitionA = Competition::factory()->create();
        $competitionB = Competition::factory()->create();
        $competitionC = Competition::factory()->create();

        $scheduledNumber = $this->phoneBook[1]->phone_number;

        PhoneLineSchedule::factory()->create([
            'competition_phone_number' => $scheduledNumber,
            'competition_id' => $competitionA->id,
            'action_at' => '2004-04-01 09:10:00',
        ]);

        PhoneLineSchedule::factory()->create([
            'competition_phone_number' => $scheduledNumber,
            'competition_id' => $competitionB->id,
            'action_at' => '2004-04-02 09:10:00',
        ]);

        PhoneLineSchedule::factory()->create([
            'competition_phone_number' => $scheduledNumber,
            'competition_id' => $competitionC->id,
            'action_at' => '2004-04-03 09:10:00',
        ]);

        $this->artisan('viveri:process-phone-line-schedule');

        $phoneLine = CompetitionPhoneLine::firstWhere('phone_number', $scheduledNumber);

        $this->assertSame($competitionC->id, $phoneLine->competition_id);
    }
}
