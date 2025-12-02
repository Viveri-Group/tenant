<?php

namespace Tests\Feature\API\CapacityCheck;

use App\Jobs\UpdateActiveCallJob;
use App\Models\ActiveCall;
use App\Models\Competition;
use App\Models\EntrantRoundCount;
use App\Models\Organisation;
use App\Models\PhoneBookEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CapacityCheckTest extends TestCase
{
    public function test_capacity_check_passes()
    {
        Bus::fake();

        $this->setFileDefaults();

        $this->login();

        $competition = Competition::factory(['start' => now()->subDay(), 'end' => now()->addDay(), 'max_entries' => 2])
            ->hasPhoneLines(['phone_number' => '0333456555'])
            ->create();

        DB::enableQueryLog();

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => '0333456555',
            'caller_phone_number' => '441604556778',
        ])
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($competition) {
                return $json
                    ->where('competition_id', $competition->id)
                    ->where('status', 'OPEN')
                    ->where('total_entry_count', 0)
                    ->where('max_entries', 2)
                    ->where('special_offer', 'FALSE')
                    ->where('sms_offer_enabled', false)
                    ->where('INTRO', 1)
                    ->where('CLI_READOUT_NOTICE', 2)
                    ->where('DTMF_MENU', 3)
                    ->where('DTMF_SUCCESS', 4)
                    ->where('DTMF_FAIL', 6)
                    ->where('COMPETITION_CLOSED', 7)
                    ->where('TOO_MANY_ENTRIES', 8)
                    ->where('total_entry_count', 0)
                    ->has('active_call_id');
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(17, $queryCount);

        $this->assertCount(1, $activeCalls = ActiveCall::all());

        $activeCall = $activeCalls->first();

        tap($activeCall, function (ActiveCall $activeCall) {
            $this->assertEquals(53, $activeCall->call_id);
            $this->assertSame('0333456555', $activeCall->phone_number);
            $this->assertSame('441604556778', $activeCall->caller_phone_number);
            $this->assertNotNull($activeCall->competition_id);
            $this->assertNull($activeCall->status);
        });

        Bus::assertDispatched(UpdateActiveCallJob::class, function ($job) use ($competition, $activeCall) {
            return $job->data === [
                    'competition_id' => $competition->id,
                    'status' => 'OPEN',
                    'active_call_id' => $activeCall->id,
                    'total_entry_count' => 0,
                    'max_entries' => 2,
                    'special_offer' => 'FALSE',
                    'sms_offer_enabled' => false,
                    'INTRO' => 1,
                    'CLI_READOUT_NOTICE' => 2,
                    'DTMF_MENU' => 3,
                    'DTMF_SUCCESS' => 4,
                    'DTMF_FAIL' => 6,
                    'COMPETITION_CLOSED' => 7,
                    'TOO_MANY_ENTRIES' => 8,
                ];
        });
    }

    public function test_capacity_check_with_competition_with_sms_offer_enabled_passes()
    {
        Bus::fake();

        $this->setFileDefaults();

        $this->login();


        $competition = Competition::factory(['start' => now()->subDay(), 'end' => now()->addDay(), 'max_entries' => 1, 'sms_offer_enabled' => true])
            ->hasPhoneLines(['phone_number' => '0333456555'])
            ->create();

        DB::enableQueryLog();

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => '0333456555',
            'caller_phone_number' => '441604556778',
        ])
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($competition) {
                return $json
                    ->where('competition_id', $competition->id)
                    ->where('status', 'OPEN')
                    ->where('total_entry_count', 0)
                    ->where('max_entries', 1)
                    ->where('special_offer', 'FALSE')
                    ->where('sms_offer_enabled', true)
                    ->where('INTRO', 1)
                    ->where('CLI_READOUT_NOTICE', 2)
                    ->where('DTMF_MENU', 3)
                    ->where('DTMF_SUCCESS', 5)
                    ->where('DTMF_FAIL', 6)
                    ->where('COMPETITION_CLOSED', 7)
                    ->where('TOO_MANY_ENTRIES', 8)
                    ->where('total_entry_count', 0)
                    ->has('active_call_id');
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(17, $queryCount);

        $this->assertCount(1, $activeCalls = ActiveCall::all());

        $activeCall = $activeCalls->first();

        tap($activeCall, function (ActiveCall $activeCall) {
            $this->assertEquals(53, $activeCall->call_id);
            $this->assertSame('0333456555', $activeCall->phone_number);
            $this->assertSame('441604556778', $activeCall->caller_phone_number);
            $this->assertNotNull($activeCall->competition_id);
            $this->assertNull($activeCall->status);
        });

        Bus::assertDispatched(UpdateActiveCallJob::class, function ($job) use ($competition, $activeCall) {
            return $job->data === [
                    'competition_id' => $competition->id,
                    'status' => 'OPEN',
                    'active_call_id' => $activeCall->id,
                    'total_entry_count' => 0,
                    'max_entries' => 1,
                    'special_offer' => 'FALSE',
                    'sms_offer_enabled' => true,
                    'INTRO' => 1,
                    'CLI_READOUT_NOTICE' => 2,
                    'DTMF_MENU' => 3,
                    'DTMF_SUCCESS' => 5,
                    'DTMF_FAIL' => 6,
                    'COMPETITION_CLOSED' => 7,
                    'TOO_MANY_ENTRIES' => 8,
                ];
        });
    }

    public function test_competition_is_found_but_is_closed()
    {
        Bus::fake();

        Carbon::setTestNow('2024-01-01 09:00:00');

        $this->setFileDefaults();

        $this->login();

        $competition = Competition::factory([
            'start' => '2024-01-01 10:00:00',
            'end' => '2024-01-31 00:00:00',
        ])
            ->hasPhoneLines(['phone_number' => '0333456555'])
            ->create();

        DB::enableQueryLog();

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => '0333456555',
            'caller_phone_number' => '441604556778',
        ])
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($competition) {
                return $json
                    ->where('competition_id', $competition->id)
                    ->where('status', 'CLOSED')
                    ->where('total_entry_count', 0)
                    ->where('max_entries', 10)
                    ->where('special_offer', 'FALSE')
                    ->where('sms_offer_enabled', false)
                    ->where('INTRO', 1)
                    ->where('CLI_READOUT_NOTICE', 2)
                    ->where('DTMF_MENU', 3)
                    ->where('DTMF_SUCCESS', 4)
                    ->where('DTMF_FAIL', 6)
                    ->where('COMPETITION_CLOSED', 7)
                    ->where('TOO_MANY_ENTRIES', 8)
                    ->where('total_entry_count', 0)
                    ->has('active_call_id');
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(17, $queryCount);

        $this->assertCount(1, $activeCalls = ActiveCall::all());

        $activeCall = $activeCalls->first();

        tap($activeCall, function (ActiveCall $activeCall) {
            $this->assertEquals(53, $activeCall->call_id);
            $this->assertSame('0333456555', $activeCall->phone_number);
            $this->assertSame('441604556778', $activeCall->caller_phone_number);
            $this->assertNotNull($activeCall->competition_id);
            $this->assertNull($activeCall->status);
        });

        Bus::assertDispatched(UpdateActiveCallJob::class, function ($job) use ($competition, $activeCall) {
            return $job->data === [
                    'competition_id' => $competition->id,
                    'status' => 'CLOSED',
                    'active_call_id' => $activeCall->id,
                    'total_entry_count' => 0,
                    'max_entries' => 10,
                    'special_offer' => 'FALSE',
                    'sms_offer_enabled' => false,
                    'INTRO' => 1,
                    'CLI_READOUT_NOTICE' => 2,
                    'DTMF_MENU' => 3,
                    'DTMF_SUCCESS' => 4,
                    'DTMF_FAIL' => 6,
                    'COMPETITION_CLOSED' => 7,
                    'TOO_MANY_ENTRIES' => 8,
                ];
        });
    }

    public function test_no_competition_is_found_but_number_is_in_phone_book_and_closed_is_returned()
    {
        Bus::fake();

        Carbon::setTestNow('2024-01-01 09:00:00');

        $this->setFileDefaults();

        $this->login();

        PhoneBookEntry::factory(['phone_number' => '0333456555'])->create();

        DB::enableQueryLog();

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => '0333456555',
            'caller_phone_number' => '441604556778',
        ])
            ->assertOk()
            ->assertJson(function (AssertableJson $json)  {
                return $json
                    ->where('competition_id', null)
                    ->where('status', 'CLOSED')
                    ->where('INTRO', 1)
                    ->where('CLI_READOUT_NOTICE', 2)
                    ->where('DTMF_MENU', 3)
                    ->where('DTMF_SUCCESS', 4)
                    ->where('DTMF_FAIL', 6)
                    ->where('COMPETITION_CLOSED', 7)
                    ->where('TOO_MANY_ENTRIES', 8)
                    ->has('active_call_id');
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(15, $queryCount);

        $this->assertCount(1, $activeCalls = ActiveCall::all());

        $activeCall = $activeCalls->first();

        tap($activeCall, function (ActiveCall $activeCall) {
            $this->assertEquals(53, $activeCall->call_id);
            $this->assertSame('0333456555', $activeCall->phone_number);
            $this->assertSame('441604556778', $activeCall->caller_phone_number);
            $this->assertNull($activeCall->competition_id);
            $this->assertNull($activeCall->status);
        });

        Bus::assertDispatched(UpdateActiveCallJob::class, function ($job) use ($activeCall) {
            return $job->data === [
                    'competition_id' => null,
                    'status' => 'CLOSED',
                    'active_call_id' => $activeCall->id,
                    'INTRO' => 1,
                    'CLI_READOUT_NOTICE' => 2,
                    'DTMF_MENU' => 3,
                    'DTMF_SUCCESS' => 4,
                    'DTMF_FAIL' => 6,
                    'COMPETITION_CLOSED' => 7,
                    'TOO_MANY_ENTRIES' => 8,
                ];
        });
    }

    public function test_capacity_check_fails_max_lines_exceeded()
    {
        Bus::fake();

        $this->setFileDefaults();

        $this->login();

        $organisation = Organisation::factory()->create();

        $competition = Competition::factory(['start' => now()->subDay(), 'end' => now()->addDay(), 'max_entries' => 2, 'organisation_id' => $organisation->id])
            ->hasPhoneLines(['phone_number' => '0333456555', 'organisation_id' => $organisation->id])
            ->create();

        $competition->organisation()->update(['max_number_of_lines' => 1]);

        ActiveCall::factory(['organisation_id' => $organisation->id])->create();

        DB::enableQueryLog();

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => '0333456555',
            'caller_phone_number' => '441604556778',
        ])
            ->assertStatus(412)
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('message', 'Active lines allowance exceeded.');
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(7, $queryCount);

        $this->assertCount(1, ActiveCall::all());
    }

    public function test_no_competition()
    {
        $this->login();

        DB::enableQueryLog();

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => '0333456555',
            'caller_phone_number' => '441604556778',
        ])
            ->assertBadRequest()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('competition_id', null)
                    ->where('active_call_id', null)
                    ->where('total_entry_count', 0)
                    ->where('special_offer', 'FALSE')
                    ->where('status', 'REJECT_CALLER');
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(8, $queryCount);

        $this->assertCount(0, ActiveCall::all());
    }

    public function test_participant_has_entered_too_many_times_via_entrant_round_count()
    {
        Carbon::setTestNow('2025-02-05 10:00:00');

        $this->setFileDefaults();

        $this->login();

        $competition = Competition::factory(['start' => '2025-02-04 09:00:00', 'end' => '2025-02-06 10:00:00', 'max_entries' => 1])
            ->hasPhoneLines(['phone_number' => '0333456555'])
            ->create();

        EntrantRoundCount::factory([
            'hash' => hash('xxh128', "2025-02-04 09:00:00 {$competition->id} 441604556778"),
            'total_entry_count' => 50,
        ])->create();

        DB::enableQueryLog();

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => '0333456555',
            'caller_phone_number' => '441604556778',
        ])
            ->assertNotAcceptable()
            ->assertJson(function (AssertableJson $json) use ($competition) {
                return $json
                    ->where('competition_id', $competition->id)
                    ->where('total_entry_count', 0)
                    ->where('special_offer', 'FALSE')
                    ->where('status', 'TOO_MANY')
                    ->whereNot('active_call_id', null);
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(15, $queryCount);
    }
}
