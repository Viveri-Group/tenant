<?php

namespace Tests\Feature\API\CapacityCheck;

use App\Jobs\UpdateActiveCallJob;
use App\Models\ActiveCall;
use App\Models\Competition;
use App\Models\EntrantRoundCount;
use App\Models\Organisation;
use App\Models\PhoneBookEntry;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CapacityCheckTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $this->setFileDefaults();

        $this->login();

        DB::enableQueryLog();

        $this->organisation = Organisation::factory()->create();

        $this->competitionNumber = '0333456555';

        PhoneBookEntry::factory(['phone_number' => $this->competitionNumber , 'organisation_id' => $this->organisation->id])->create();

        $this->competition = Competition::factory(['start' => now()->subDay(), 'end' => now()->addDay(), 'max_entries' => 2, 'organisation_id' => $this->organisation->id])
            ->hasPhoneLines(['phone_number' => $this->competitionNumber, 'organisation_id' => $this->organisation->id])
            ->create();
    }

    public function test_capacity_check_passes()
    {
        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => $this->competitionNumber,
            'caller_phone_number' => '441604556778',
        ])
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('competition_id', $this->competition->id)
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

        $this->assertCount(1, $activeCalls = ActiveCall::all());

        $activeCall = $activeCalls->first();

        tap($activeCall, function (ActiveCall $activeCall) {
            $this->assertEquals(53, $activeCall->call_id);
            $this->assertSame($this->competitionNumber, $activeCall->phone_number);
            $this->assertSame('441604556778', $activeCall->caller_phone_number);
            $this->assertNotNull($activeCall->competition_id);
            $this->assertNull($activeCall->status);
        });

        Bus::assertDispatched(UpdateActiveCallJob::class, function ($job) use ($activeCall) {
            return $job->data === [
                    'competition_id' => $this->competition->id,
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

    public function test_phone_book_entry_is_missing()
    {
        Competition::factory(['start' => now()->subDay(), 'end' => now()->addDay(), 'max_entries' => 2])
            ->hasPhoneLines(['phone_number' => '445555777777'])
            ->create();

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => '445555777777',
            'caller_phone_number' => '441604556778',
        ])
            ->assertStatus(407)
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('competition_id', null)
                    ->where('status', 'REJECT_CALLER')
                    ->where('total_entry_count', 0)
                    ->where('special_offer', 'FALSE')
                    ->has('active_call_id');
            });

        $this->assertCount(0, ActiveCall::all());

        Bus::assertNotDispatched(UpdateActiveCallJob::class);
    }

    public function test_no_competition()
    {
        $this->competition->phoneLines()->delete();

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => $this->competitionNumber,
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

        $this->assertCount(0, ActiveCall::all());
    }

    public function test_competition_is_closed()
    {
        $this->competition->update([
            'start' => '2024-01-01 10:00:00',
            'end' => '2024-01-31 00:00:00',
        ]);

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => $this->competitionNumber,
            'caller_phone_number' => '441604556778',
        ])
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('competition_id', $this->competition->id)
                    ->where('status', 'CLOSED')
                    ->where('total_entry_count', 0)
                    ->where('max_entries', 2)
                    ->where('special_offer', 'FALSE')
                    ->where('sms_offer_enabled', 'FALSE')
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

        $this->assertCount(1, $activeCalls = ActiveCall::all());

        $activeCall = $activeCalls->first();

        tap($activeCall, function (ActiveCall $activeCall) {
            $this->assertEquals(53, $activeCall->call_id);
            $this->assertSame($this->competitionNumber, $activeCall->phone_number);
            $this->assertSame('441604556778', $activeCall->caller_phone_number);
            $this->assertNotNull($activeCall->competition_id);
            $this->assertNull($activeCall->status);
        });

        Bus::assertDispatched(UpdateActiveCallJob::class, function ($job) use ($activeCall) {
            return $job->data === [
                    'competition_id' => $this->competition->id,
                    'status' => 'CLOSED',
                    'active_call_id' => $activeCall->id,
                    'total_entry_count' => 0,
                    'max_entries' => 2,
                    'special_offer' => 'FALSE',
                    'sms_offer_enabled' => 'FALSE',
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
        $this->competition->update(['sms_offer_enabled' => true]);

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => $this->competitionNumber,
            'caller_phone_number' => '441604556778',
        ])
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('competition_id', $this->competition->id)
                    ->where('status', 'OPEN')
                    ->where('total_entry_count', 0)
                    ->where('max_entries', 2)
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

        $this->assertCount(1, $activeCalls = ActiveCall::all());

        $activeCall = $activeCalls->first();

        tap($activeCall, function (ActiveCall $activeCall) {
            $this->assertEquals(53, $activeCall->call_id);
            $this->assertSame($this->competitionNumber, $activeCall->phone_number);
            $this->assertSame('441604556778', $activeCall->caller_phone_number);
            $this->assertNotNull($activeCall->competition_id);
            $this->assertNull($activeCall->status);
        });

        Bus::assertDispatched(UpdateActiveCallJob::class, function ($job) use ($activeCall) {
            return $job->data === [
                    'competition_id' => $this->competition->id,
                    'status' => 'OPEN',
                    'active_call_id' => $activeCall->id,
                    'total_entry_count' => 0,
                    'max_entries' => 2,
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

    public function test_fails_max_lines_exceeded()
    {
        $this->organisation->update(['max_number_of_lines' => 1]);

        ActiveCall::factory(['organisation_id' => $this->organisation->id])->create();

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => $this->competitionNumber,
            'caller_phone_number' => '441604556778',
        ])
            ->assertStatus(412)
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('message', 'Active lines allowance exceeded.');
            });

        $this->assertCount(1, ActiveCall::all());
    }


    public function test_participant_has_entered_too_many_times_via_entrant_round_count()
    {
        EntrantRoundCount::factory([
            'hash' => hash('xxh128', "{$this->competition->start} {$this->competition->id} 441604556778"),
            'total_entry_count' => 50,
        ])->create();

        $this->post(route('active-call.capacity-check'), [
            'call_id' => 53,
            'phone_number' => $this->competitionNumber,
            'caller_phone_number' => '441604556778',
        ])
            ->assertNotAcceptable()
            ->assertJson(function (AssertableJson $json){
                return $json
                    ->where('competition_id', $this->competition->id)
                    ->where('total_entry_count', 0)
                    ->where('special_offer', 'FALSE')
                    ->where('status', 'TOO_MANY')
                    ->whereNot('active_call_id', null);
            });
    }
}
