<?php

namespace Tests\Feature\API\Competition\Draw;

use App\Jobs\MarkParticipantsAsDrawnJob;
use App\Models\Competition;
use App\Models\CompetitionDraw;
use App\Models\Participant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DrawWholeCompetitionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2024-01-01 09:00:00');

        $this->login();

        $this->competitionA = Competition::factory([
            'name' => 'Test Competition',
            'type' => 'WHOLE_COMPETITION',
            'start' => now()->subDays(7),
            'end' => now()->subMinute(),
            ])->hasPhoneLines(['phone_number' => '03000111111'])->create();

        Participant::factory([
            'competition_id' => $this->competitionA->id,
            'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id,
            'call_start' => now()->subDays(2),
        ])->count(2)->create();

        $competitionB = Competition::factory()->hasPhoneLines(['phone_number' => '03000111111'])->create();
        Participant::factory(['competition_id' => $competitionB->id, 'competition_phone_line_id' => $competitionB->phoneLines()->first()->id])->count(10)->create();
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
        $this->assertCount(12, Participant::whereNull('competition_draw_id')->whereNull('drawn_at')->get());
        $this->assertCount(0, CompetitionDraw::all());

        $this->post(route('competition.mark-as-drawn',
            [
                'competition' => $this->competitionA,
                'drawn_by' => 'bar',
            ]
        ))->assertNoContent();

        $this->assertCount(1, $competitionDraw = CompetitionDraw::all());
        $this->assertCount(2, $spentEntries = Participant::where('competition_id', $this->competitionA->id)->whereNotNull('drawn_at')->get());
        $this->assertCount(10, Participant::whereNull('competition_draw_id')->whereNull('drawn_at')->get());

        $competitionDraw = $competitionDraw->first();

        tap($competitionDraw, function (CompetitionDraw $draw) {
            $this->assertSame($this->competitionA->id, $draw->competition_id);
            $this->assertSame($this->competitionA->type, $draw->competition_type);
            $this->assertSame('bar', $draw->drawn_by);
        });

        tap($spentEntries, function (Collection $entries) use ($competitionDraw) {
            $entryA = $entries->get(0);
            $entryB = $entries->get(1);

            $this->assertSame($this->competitionA->id, $entryA->competition_id);
            $this->assertSame($this->competitionA->phoneLines()->first()->id, $entryA->competition_phone_line_id);
            $this->assertSame($competitionDraw->id, $entryA->competition_draw_id);
            $this->assertNotNull($entryA->drawn_at);

            $this->assertSame($this->competitionA->id, $entryB->competition_id);
            $this->assertSame($this->competitionA->phoneLines()->first()->id, $entryB->competition_phone_line_id);
            $this->assertNotNull($entryB->competition_draw_id);
            $this->assertSame($competitionDraw->id, $entryB->competition_draw_id);
            $this->assertNotNull($entryB->drawn_at);
        });
    }

    public function test_cant_draw_a_competition_that_has_been_drawn()
    {
        $this->assertCount(12, Participant::whereNull('competition_draw_id')->whereNull('drawn_at')->get());

        CompetitionDraw::factory()->create([
            'competition_id' => $this->competitionA->id,
            'round_from' => $this->competitionA->start->format('Y-m-d'),
            'round_to' => $this->competitionA->end->format('Y-m-d'),
        ]);

        $this->post(route('competition.mark-as-drawn', $this->competitionA))->assertBadRequest();

        $this->assertCount(12, Participant::whereNull('competition_draw_id')->whereNull('drawn_at')->get());

        $this->assertCount(1, CompetitionDraw::all());
    }

    public function test_cant_be_drawn_before_end_date()
    {
        $this->competitionA->update(['end' => now()->addMinutes(10)]);

        $this->assertCount(12, Participant::whereNull('competition_draw_id')->whereNull('drawn_at')->get());

        $this->post(route('competition.mark-as-drawn', $this->competitionA))->assertUnauthorized();

        $this->assertCount(12, Participant::whereNull('competition_draw_id')->whereNull('drawn_at')->get());

        $this->assertCount(0, CompetitionDraw::all());
    }
}
