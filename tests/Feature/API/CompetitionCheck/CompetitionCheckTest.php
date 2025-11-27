<?php

namespace Tests\Feature\API\CompetitionCheck;

use App\Models\ActiveCall;
use App\Models\Competition;
use App\Models\EntrantRoundCount;
use App\Models\PhoneBookEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CompetitionCheckTest extends TestCase
{
    public function test_active_lines_exceeded_is_turned_off()
    {
        $this->login();

        Config::set('system.ENFORCE_MAX_NUMBER_OF_LINES', false);

        $response = $this->postJson(route('active-call.competition-check'), [
            'call_id' => 53,
            'phone_number' => '44333456555',
            'caller_phone_number' => '441604556778',
        ]);

        $this->assertNotEquals(412, $response->getStatusCode());
    }

    public function test_active_lines_exceeded_fails()
    {
        $this->markTestSkipped('removed for time being');

        $this->login();

        Config::set('system.ENFORCE_MAX_NUMBER_OF_LINES', true);
        Config::set('system.MAX_NUMBER_OF_LINES', 0);

        DB::enableQueryLog();

        $this->postJson(route('active-call.competition-check'), [
            'call_id' => 53,
            'phone_number' => '44333456555',
            'caller_phone_number' => '441604556778',
        ])
            ->assertStatus(412)
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('message', 'Active lines allowance exceeded.');
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(5, $queryCount);

        $this->assertCount(0, ActiveCall::all());
    }

    public function test_no_competition_is_found_and_reject_is_returned()
    {
        Bus::fake();

        Carbon::setTestNow('2024-01-01 09:00:00');

        $this->setFileDefaults();

        $this->login();

        Config::set('system.MAX_NUMBER_OF_LINES', 50);

        PhoneBookEntry::factory(['phone_number' => '44333456555'])->create();

        DB::enableQueryLog();

        $this->postJson(route('active-call.competition-check'), [
            'call_id' => 53,
            'phone_number' => '44333456555',
            'caller_phone_number' => '441604556778',
        ])
            ->assertStatus(400)
            ->assertJson(function (AssertableJson $json)  {
                return $json
                    ->where('competition_id', null)
                    ->where('status', 'REJECT_CALLER')
                    ->where('total_entry_count', 0)
                    ->where('entries_warning', 0)
                    ->where('max_paid_entries', null)
                    ->where('special_offer', 'FALSE')
                    ->has('active_call_id');
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(5, $queryCount);

        $this->assertCount(0, ActiveCall::all());
    }

    public function test_competition_is_in_a_state_of_pre_open()
    {
        Carbon::setTestNow('2024-01-10 09:00:00');

        $this->login();

        $competition = Competition::factory([
            'start' => '2024-01-10 15:10:00',
            'end' => '2024-01-15 15:00:00',
        ])
            ->hasPhoneLines(1,['phone_number' => '44333456555'])
            ->create();

        DB::enableQueryLog();

        $this->postJson(route('active-call.competition-check'), [
            'call_id' => 53,
            'phone_number' => '44333456555',
            'caller_phone_number' => '441604556778',
        ])
            ->assertStatus(410)
            ->assertJson(function (AssertableJson $json) use($competition){
                return $json
                    ->where('competition_id', $competition->id)
                    ->has('active_call_id')
                    ->where('status', 'PRE_OPEN');
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(7, $queryCount);

        $this->assertCount(0, ActiveCall::all());
    }

    public function test_competition_is_in_a_state_of_closed()
    {
        Carbon::setTestNow('2024-01-15 16:00:00');

        $this->login();

        $competition = Competition::factory([
            'start' => '2024-01-10 15:10:00',
            'end' => '2024-01-15 15:00:00',
            'max_paid_entries' => 2,
        ])
            ->hasPhoneLines(1,['phone_number' => '44333456555'])
            ->create();

        DB::enableQueryLog();

        $this->postJson(route('active-call.competition-check'), [
            'call_id' => 53,
            'phone_number' => '44333456555',
            'caller_phone_number' => '441604556778',
        ])
            ->assertStatus(411)
            ->assertJson(function (AssertableJson $json) use($competition){
                return $json
                    ->where('competition_id', $competition->id)
                    ->has('active_call_id')
                    ->where('status', 'CLOSED')
                    ->where('total_entry_count', 0)
                    ->where('entries_warning', 0)
                    ->where('max_paid_entries', 2)
                    ->where('special_offer', 'FALSE');
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(10, $queryCount);

        $this->assertCount(0, ActiveCall::all());
    }

    public function test_capacity_check_passes()
    {
        Carbon::setTestNow('2024-01-11 16:00:00');

        $this->login();

        $competition = Competition::factory([
            'start' => '2024-01-10 15:10:00',
            'end' => '2024-01-15 15:00:00',
            'max_paid_entries' => 4,
            'special_offer' => 'BOGOF',
        ])
            ->hasPhoneLines(1,['phone_number' => '44333456555'])
            ->create();

        DB::enableQueryLog();

        $this->postJson(route('active-call.competition-check'), [
            'call_id' => 53,
            'phone_number' => '44333456555',
            'caller_phone_number' => '441604556778',
            'cli_presentation' => 0
        ])
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use($competition){
                return $json
                    ->where('competition_id', $competition->id)
                    ->has('active_call_id')
                    ->where('status', 'OPEN')
                    ->where('total_entry_count', 0)
                    ->where('entries_warning', 0)
                    ->where('max_paid_entries', 4)
                    ->where('special_offer', 'BOGOF');
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(12, $queryCount);

        $this->assertCount(1, $activeCalls = ActiveCall::all());

        $activeCall = $activeCalls->first();

        tap($activeCall, function (ActiveCall $activeCall) use($competition) {
            $this->assertEquals(53, $activeCall->call_id);
            $this->assertSame('44333456555', $activeCall->phone_number);
            $this->assertSame('441604556778', $activeCall->caller_phone_number);
            $this->assertSame($competition->id, $activeCall->competition_id);
            $this->assertSame(0, $activeCall->cli_presentation);
            $this->assertSame('OPEN_PRE_ANSWER', $activeCall->status);
        });
    }

    public function test_capacity_check_passes_with_multiple_entry_count()
    {
        Carbon::setTestNow('2024-01-11 16:00:00');

        $this->login();

        $competition = Competition::factory([
            'start' => '2024-01-10 15:10:00',
            'end' => '2024-01-15 15:00:00',
            'entries_warning' => 4,
            'max_paid_entries'=> 3
        ])
            ->hasPhoneLines(1,['phone_number' => '44333456555'])
            ->create();

        EntrantRoundCount::factory()->create([
            'hash' => hash('xxh128', "{$competition->id} 441604556778"),
            'total_entry_count' => 15,
        ]);

        DB::enableQueryLog();

        $this->postJson(route('active-call.competition-check'), [
            'call_id' => 53,
            'phone_number' => '44333456555',
            'caller_phone_number' => '441604556778',
        ])
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use($competition){
                return $json
                    ->where('competition_id', $competition->id)
                    ->has('active_call_id')
                    ->where('status', 'OPEN')
                    ->where('total_entry_count', 15)
                    ->where('entries_warning', 4)
                    ->where('max_paid_entries', 3)
                    ->where('special_offer', 'FALSE');
            });

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(12, $queryCount);

        $this->assertCount(1, $activeCalls = ActiveCall::all());

        $activeCall = $activeCalls->first();

        tap($activeCall, function (ActiveCall $activeCall) use($competition) {
            $this->assertEquals(53, $activeCall->call_id);
            $this->assertSame('44333456555', $activeCall->phone_number);
            $this->assertSame('441604556778', $activeCall->caller_phone_number);
            $this->assertSame($competition->id, $activeCall->competition_id);
            $this->assertSame(2, $activeCall->cli_presentation);
            $this->assertSame('OPEN_PRE_ANSWER', $activeCall->status);
        });
    }
}
