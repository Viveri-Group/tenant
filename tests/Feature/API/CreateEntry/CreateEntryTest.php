<?php

namespace Tests\Feature\API\CreateEntry;

use App\Jobs\CreateEntryJob;
use App\Models\ActiveCall;
use App\Models\EntrantRoundCount;
use App\Models\Participant;
use Illuminate\Support\Facades\Bus;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CreateEntryTest extends TestCase
{
    public function test_validation()
    {
        $this->login();

        $activeCall = ActiveCall::factory()->create();

        $this->postJson(route('active-call.create-entry', $activeCall))
            ->assertOk();
    }

    public function test_create_free_entry_is_boolean()
    {
        $this->login();

        $activeCall = ActiveCall::factory()->create();

        $this->postJson(route('active-call.create-entry', $activeCall), [
            'create_free_entry' => 'Yes'
        ])
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->has('message')
                    ->where('data.0.source', 'create_free_entry');
            });
    }

    public function test_create_entry_job_is_dispatched_with_correct_data()
    {
        Bus::fake();

        $this->login();

        $activeCall = ActiveCall::factory()->create();

        $this->postJson(route('active-call.create-entry', $activeCall))
            ->assertOk();

        Bus::assertDispatched(CreateEntryJob::class, function (CreateEntryJob $job) use ($activeCall) {
            $this->assertEquals($activeCall->competition_id, $job->activeCallDTO->competition_id);
            $this->assertEquals($activeCall->call_id, $job->activeCallDTO->call_id);
            $this->assertNull($job->activeCallDTO->participant_id);
            $this->assertEquals($activeCall->phone_number, $job->activeCallDTO->competition_phone_number);
            $this->assertEquals($activeCall->caller_phone_number, $job->activeCallDTO->caller_phone_number);
            $this->assertEquals($activeCall->status, $job->activeCallDTO->status);
            $this->assertEquals($activeCall->status, $job->activeCallDTO->status);
            $this->assertEquals($activeCall->round_start, $job->activeCallDTO->round_start);
            $this->assertEquals($activeCall->round_end, $job->activeCallDTO->round_end);
            $this->assertNull($job->activeCallDTO->call_end);
            $this->assertEquals($activeCall->cli_presentation, $job->activeCallDTO->cli_presentation);
            $this->assertEquals($activeCall->created_at, $job->activeCallDTO->created_at);
            $this->assertNull($job->activeCallDTO->audioFileNumber);

            return true;
        });
    }

    public function test_participant_is_created()
    {
        $this->login();

        $activeCall = ActiveCall::factory()->create();

        $this->postJson(route('active-call.create-entry', $activeCall))
            ->assertOk();

        $activeCall->refresh();

        $this->assertCount(1, $participants = Participant::all());

        tap($participants->first(), function ($participant) use ($activeCall) {
            $this->assertSame($activeCall->call_id, $participant->call_id);
            $this->assertSame($activeCall->created_at->format('Y-m-d H:i:s'), $participant->call_start->format('Y-m-d H:i:s'));
            $this->assertNull($participant->call_end);
            $this->assertSame($activeCall->competition_id, $participant->competition_id);
            $this->assertSame($activeCall->phone_number, $participant->competition_phone_number);
            $this->assertSame($activeCall->caller_phone_number, $participant->telephone);
            $this->assertSame($activeCall->round_start, $participant->round_start->format('Y-m-d H:i:s'));
            $this->assertSame($activeCall->round_end, $participant->round_end->format('Y-m-d H:i:s'));

            $this->assertSame($activeCall->participant_id, $participant->id);
        });
    }

    public function test_second_call_gets_blocked()
    {
        $this->login();

        $activeCall = ActiveCall::factory()->create();

        $this->assertNull($activeCall->participant_id);

        $this->postJson(route('active-call.create-entry', $activeCall))->assertOk();
        $activeCall->refresh();
        $this->assertNotNull($activeCall->participant_id);

        $this->postJson(route('active-call.create-entry', $activeCall))->assertConflict();

        $this->assertCount(1, EntrantRoundCount::all());
    }
}
