<?php

namespace Tests\Feature\API\Competition\Draw;

use App\Jobs\MarkParticipantsAsDrawnJob;
use App\Models\Competition;
use App\Models\CompetitionDraw;
use App\Models\Participant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DrawWeeklyCompetitionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2024-01-31 17:00:00');

        $this->login();

        $this->competitionA = Competition::factory([
            'name' => 'Test Competition',
            'type' => 'WEEKLY',
            'start' => '2024-01-01 09:00:00',
            'end' => '2024-01-31 17:00:00',
            'active_from' => '15:10:00',
            'active_to' => '15:00:00',
            'day_of_week' => 'SATURDAY',
        ])->hasPhoneLines([
            'phone_number' => '03000111111'
        ])->create();

        $this->participantA = Participant::factory([ //competition_draw_id should be null
            'competition_id' => $this->competitionA->id,
            'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id,
            'call_start' => '2024-01-05 15:15:00'
        ])->create();

        $this->participantB = Participant::factory([ //should have competition_draw_id of first draw
            'competition_id' => $this->competitionA->id,
            'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id,
            'call_start' => '2024-01-06 15:20:00'
        ])->create();

        $this->participantC = Participant::factory([ //should have competition_draw_id of first draw
            'competition_id' => $this->competitionA->id,
            'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id,
            'call_start' => '2024-01-09 17:10:00'
        ])->create();

        $this->participantD = Participant::factory([ //should have competition_draw_id of second draw
            'competition_id' => $this->competitionA->id,
            'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id,
            'call_start' => '2024-01-14 15:00:00'
        ])->create();

        $this->participantE = Participant::factory([ //competition_draw_id should be null
            'competition_id' => $this->competitionA->id,
            'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id,
            'call_start' => '2024-01-28 15:11:00'
        ])->create();
    }

    public function test_running_draw_calls_job_as_expected()
    {
        Bus::fake();

        $this->post(route('competition.mark-as-drawn',
            [
                'competition' => $this->competitionA,
                'drawn_by' => 'bar',
            ]
        ))->assertNoContent();

        Bus::assertDispatched(MarkParticipantsAsDrawnJob::class,  function (MarkParticipantsAsDrawnJob $job) {
            $this->assertSame($this->competitionA->id, $job->competition->id);
            $this->assertSame('bar', $job->drawnBy);

            return true;
        });
    }

    public function test_running_draw()
    {
        $this->assertCount(5, Participant::whereNull('competition_draw_id')->whereNull('drawn_at')->get());
        $this->assertCount(0, CompetitionDraw::all());

        $this->post(route('competition.mark-as-drawn',
            [
                'competition' => $this->competitionA,
                'drawn_by' => null
            ]
        ))->assertNoContent();

        $this->assertCount(3, $competitionDraws = CompetitionDraw::all());

        $this->participantA->refresh();
        $this->assertNull($this->participantA->competition_draw_id);
        $this->assertNull($this->participantA->drawn_at);

        $this->participantB->refresh();
        $this->assertSame($this->participantB->competition_draw_id, $competitionDraws->get(0)->id);
        $this->assertNotNull($this->participantB->drawn_at);

        $this->participantC->refresh();
        $this->assertSame($this->participantC->competition_draw_id, $competitionDraws->get(0)->id);
        $this->assertNotNull($this->participantC->drawn_at);

        $this->participantD->refresh();
        $this->assertSame($this->participantD->competition_draw_id, $competitionDraws->get(1)->id);
        $this->assertNotNull($this->participantD->drawn_at);

        $this->participantE->refresh();
        $this->assertNull($this->participantE->competition_draw_id);
        $this->assertNull($this->participantE->drawn_at);
        $this->assertNull($this->participantE->drawn_by);

        tap($competitionDraws->get(0), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'WEEKLY', $draw->competition_type);
        });

        tap($competitionDraws->get(1), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'WEEKLY', $draw->competition_type);
        });

        tap($competitionDraws->get(2), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'WEEKLY', $draw->competition_type);
        });
    }

    public function test_running_draw_too_early_before_a_round_has_ended()
    {
        Carbon::setTestNow('2024-01-05 17:00:00');

        $this->post(route('competition.mark-as-drawn', $this->competitionA))->assertBadRequest();
    }

    public function test_running_draw_twice()
    {
        $this->assertCount(0, CompetitionDraw::all());

        $this->post(route('competition.mark-as-drawn', $this->competitionA))->assertNoContent();

        $this->assertCount(3, CompetitionDraw::all());

        $this->post(route('competition.mark-as-drawn', $this->competitionA))->assertNoContent();

        $this->assertCount(3, CompetitionDraw::all());
    }
}
