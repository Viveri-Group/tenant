<?php

namespace Tests\Feature\API\Competition;

use App\Action\Competition\CompetitionClearDownSuccessAction;
use App\Jobs\SMSFirstEntryJob;
use App\Jobs\SMSOfferAcceptedJob;
use App\Models\ActiveCall;
use App\Models\EntrantRoundCount;
use App\Models\FailedEntry;
use App\Models\Participant;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CompetitionClearDownSuccessActionTest extends TestCase
{
    public function test_status_of_success_and_sms_offer_true()
    {
        Bus::fake();

        $this->login();

        $activeCall = ActiveCall::factory()->hasCompetitionPhoneLine()->create();
        $activeCallDTO = $this->getActiveCallDTO($activeCall);

        (new CompetitionClearDownSuccessAction())->handle($activeCallDTO,true);

        $this->assertCount(0, FailedEntry::all());
        $this->assertCount(1, $participants = Participant::all());
        $this->assertCount(1, $entrantRoundCount = EntrantRoundCount::all());

        $participant = $participants->first();

        tap($participant, function (Participant $participant) use ($activeCall) {
            $this->assertEquals($activeCall->call_id, $participant->call_id);
            $this->assertSame($activeCall->created_at->format('Y-m-d\TH:i:s.u\Z'), $participant->call_start->format('Y-m-d\TH:i:s.u\Z'));
            $this->assertSame($activeCall->competition->id, $participant->competition_id);
            $this->assertSame($activeCall->competition->phoneLines()->first()->id, $participant->competition_phone_line_id);
            $this->assertSame($activeCall->caller_phone_number, $participant->telephone);
            $this->assertTrue($participant->sms_offer_accepted);
            $this->assertNull($participant->competition_draw_id);
            $this->assertNull($participant->drawn_at);
            $this->assertNull($participant->deleted_at);
        });

        tap($entrantRoundCount->first(), function (EntrantRoundCount $entrantRoundCount) use ($activeCall) {
            $this->assertEquals(hash('xxh128', "{$activeCall->round_start} {$activeCall->competition_id} {$activeCall->caller_phone_number}"), $entrantRoundCount->hash);
            $this->assertSame($activeCall->competition_id, $entrantRoundCount->competition_id);
            $this->assertSame($activeCall->caller_phone_number, $entrantRoundCount->caller_number);
            $this->assertSame(1, $entrantRoundCount->entry_count);
        });

        Bus::assertDispatched(SMSOfferAcceptedJob::class, function(SMSOfferAcceptedJob $job) use ($activeCall) {
            $this->assertSame($activeCall->competition->id, $job->competitionId);
            $this->assertSame($activeCall->caller_phone_number, $job->callerNumber);

            return true;
        });

        Bus::assertDispatched(SMSFirstEntryJob::class, function(SMSFirstEntryJob $job) use ($activeCall) {
            $this->assertSame($activeCall->competition->id, $job->competitionId);
            $this->assertSame($activeCall->caller_phone_number, $job->callerNumber);

            return true;
        });

        $this->assertTrue($participant->sms_first_entry_sent);
    }

    public function test_status_of_success_and_sms_offer_false()
    {
        Bus::fake();

        $this->login();

        $activeCall = ActiveCall::factory()->hasCompetitionPhoneLine()->create();
        $activeCallDTO = $this->getActiveCallDTO($activeCall);

        (new CompetitionClearDownSuccessAction())->handle($activeCallDTO,false);

        $participant = Participant::first()->first();

        $this->assertFalse($participant->sms_offer_accepted);

        Bus::assertNotDispatched(SMSOfferAcceptedJob::class);
    }

    public function test_status_of_success_but_entrant_count_is_greater_than_1_so_first_entry_job_is_not_sent()
    {
        Bus::fake();

        $this->login();

        $activeCall = ActiveCall::factory()->hasCompetitionPhoneLine()->create();
        $activeCallDTO = $this->getActiveCallDTO($activeCall);

        EntrantRoundCount::factory([
            'hash' => hash('xxh128', "{$activeCallDTO->round_start} {$activeCallDTO->competition_id} {$activeCallDTO->caller_phone_number}"),
            'competition_id' => $activeCallDTO->competition_id,
            'caller_number' => $activeCallDTO->caller_phone_number,
            'entry_count' => 5,
        ])->create();

        (new CompetitionClearDownSuccessAction())->handle($activeCallDTO,false);

        $participant = Participant::first()->first();


        $this->assertFalse($participant->sms_first_entry_sent);

        Bus::assertNotDispatched(SMSFirstEntryJob::class);
    }

    public function test_status_of_success_and_sms_offer_not_set()
    {
        Bus::fake();

        $this->login();

        $activeCall = ActiveCall::factory()->hasCompetitionPhoneLine()->create();
        $activeCallDTO = $this->getActiveCallDTO($activeCall);

        (new CompetitionClearDownSuccessAction())->handle($activeCallDTO,false);

        $participant = Participant::first()->first();

        $this->assertFalse($participant->sms_offer_accepted);

        Bus::assertNotDispatched(SMSOfferAcceptedJob::class);
    }
}
