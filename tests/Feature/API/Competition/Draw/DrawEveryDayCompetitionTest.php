<?php

namespace Tests\Feature\API\Competition\Draw;

use App\Jobs\MarkParticipantsAsDrawnJob;
use App\Models\Competition;
use App\Models\CompetitionDraw;
use App\Models\Participant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DrawEveryDayCompetitionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2024-01-31 17:00:00');

        $this->login();

        $this->competitionA = Competition::factory([
            'name' => 'Test Competition',
            'type' => 'EVERYDAY',
            'start' => '2024-01-01 09:00:00',
            'end' => '2024-01-31 17:00:00',
            'active_from' => '15:10:00',
            'active_to' => '15:00:00',
        ])->hasPhoneLines([
            'phone_number' => '03000111111'
        ])->create();

        $this->participantA = Participant::factory([ //should have competition_draw_id of 5th draw
            'competition_id' => $this->competitionA->id,
            'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id,
            'call_start' => '2024-01-05 15:15:00'
        ])->create();

        $this->participantB = Participant::factory([ //should have competition_draw_id of 6th draw
            'competition_id' => $this->competitionA->id,
            'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id,
            'call_start' => '2024-01-06 15:20:00'
        ])->create();

        $this->participantC = Participant::factory([ //should have competition_draw_id of 9th draw
            'competition_id' => $this->competitionA->id,
            'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id,
            'call_start' => '2024-01-09 17:10:00'
        ])->create();

        $this->participantD = Participant::factory([ //should have competition_draw_id of 14th draw
            'competition_id' => $this->competitionA->id,
            'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id,
            'call_start' => '2024-01-14 15:00:00'
        ])->create();

        $this->participantE = Participant::factory([ //should have competition_draw_id of 28th draw
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
                'drawn_by' => 'bar'
            ]
        ))->assertNoContent();

        $this->assertCount(30, $competitionDraws = CompetitionDraw::all());

        $this->participantA->refresh();
        $this->assertSame($this->participantA->competition_draw_id, $competitionDraws->get(4)->id);
        $this->assertNotNull($this->participantA->drawn_at);

        $this->participantB->refresh();
        $this->assertSame($this->participantB->competition_draw_id, $competitionDraws->get(5)->id);
        $this->assertNotNull($this->participantB->drawn_at);

        $this->participantC->refresh();
        $this->assertSame($this->participantC->competition_draw_id, $competitionDraws->get(8)->id);
        $this->assertNotNull($this->participantC->drawn_at);

        $this->participantD->refresh();
        $this->assertSame($this->participantD->competition_draw_id, $competitionDraws->get(12)->id);
        $this->assertNotNull($this->participantD->drawn_at);

        $this->participantE->refresh();
        $this->assertSame($this->participantE->competition_draw_id, $competitionDraws->get(27)->id);
        $this->assertNotNull($this->participantE->drawn_at);

        tap($competitionDraws->get(0), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-01', $draw->round_from);
           $this->assertSame( '2024-01-02', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertSame( 'bar', $draw->drawn_by);
        });

        tap($competitionDraws->get(1), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-02', $draw->round_from);
           $this->assertSame( '2024-01-03', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(2), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-03', $draw->round_from);
           $this->assertSame( '2024-01-04', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(3), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-04', $draw->round_from);
           $this->assertSame( '2024-01-05', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(4), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-05', $draw->round_from);
           $this->assertSame( '2024-01-06', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(5), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-06', $draw->round_from);
           $this->assertSame( '2024-01-07', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(6), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-07', $draw->round_from);
           $this->assertSame( '2024-01-08', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(7), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-08', $draw->round_from);
           $this->assertSame( '2024-01-09', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(8), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-09', $draw->round_from);
           $this->assertSame( '2024-01-10', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(9), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-10', $draw->round_from);
           $this->assertSame( '2024-01-11', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(10), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-11', $draw->round_from);
           $this->assertSame( '2024-01-12', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(11), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-12', $draw->round_from);
           $this->assertSame( '2024-01-13', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(12), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-13', $draw->round_from);
           $this->assertSame( '2024-01-14', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(13), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-14', $draw->round_from);
           $this->assertSame( '2024-01-15', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(14), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-15', $draw->round_from);
           $this->assertSame( '2024-01-16', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(15), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-16', $draw->round_from);
           $this->assertSame( '2024-01-17', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(16), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-17', $draw->round_from);
           $this->assertSame( '2024-01-18', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(17), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-18', $draw->round_from);
           $this->assertSame( '2024-01-19', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(18), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-19', $draw->round_from);
           $this->assertSame( '2024-01-20', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(19), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-20', $draw->round_from);
           $this->assertSame( '2024-01-21', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(20), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-21', $draw->round_from);
           $this->assertSame( '2024-01-22', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(21), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-22', $draw->round_from);
           $this->assertSame( '2024-01-23', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(22), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-23', $draw->round_from);
           $this->assertSame( '2024-01-24', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(23), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-24', $draw->round_from);
           $this->assertSame( '2024-01-25', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(24), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-25', $draw->round_from);
           $this->assertSame( '2024-01-26', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(25), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-26', $draw->round_from);
           $this->assertSame( '2024-01-27', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(26), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-27', $draw->round_from);
           $this->assertSame( '2024-01-28', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(27), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-28', $draw->round_from);
           $this->assertSame( '2024-01-29', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(28), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-29', $draw->round_from);
           $this->assertSame( '2024-01-30', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });

        tap($competitionDraws->get(29), function ($draw) {
           $this->assertSame( $this->competitionA->id, $draw->competition_id);
           $this->assertSame( 'EVERYDAY', $draw->competition_type);
           $this->assertSame( '2024-01-30', $draw->round_from);
           $this->assertSame( '2024-01-31', $draw->round_to);
           $this->assertNotNull( $draw->round_hash);
           $this->assertNotNull( $draw->drawn_by);
        });
    }

    public function test_running_draw_too_early_before_a_round_has_ended()
    {
        Carbon::setTestNow('2024-01-01 15:00:00');

        $this->post(route('competition.mark-as-drawn', $this->competitionA))->assertBadRequest();
    }

    public function test_running_draw_twice()
    {
        $this->assertCount(0, CompetitionDraw::all());

        $this->post(route('competition.mark-as-drawn', $this->competitionA))->assertNoContent();

        $this->assertCount(30, CompetitionDraw::all());

        $this->post(route('competition.mark-as-drawn', $this->competitionA))->assertNoContent();

        $this->assertCount(30, CompetitionDraw::all());
    }
}
