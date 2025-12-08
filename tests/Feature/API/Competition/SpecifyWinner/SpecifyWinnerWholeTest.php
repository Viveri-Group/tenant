<?php

namespace Tests\Feature\API\Competition\SpecifyWinner;

use App\Models\Competition;
use App\Models\CompetitionDraw;
use App\Models\CompetitionWinner;
use App\Models\Participant;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SpecifyWinnerWholeTest extends TestCase
{
    public function setUp(): void
    {
        Carbon::setTestNow('2024-10-31 15:30:00');
        parent::setUp();

        $this->login();

        $this->winningPosition = 7;

        $this->competitionA = Competition::factory([
            'type' => 'WHOLE_COMPETITION',
            'start' => '2024-10-01 15:10:00',
            'end' => '2024-10-31 15:00:00',
        ])->hasPhoneLines(['phone_number' => '03000111111'])->create();

        $competitionDraw = CompetitionDraw::factory([
            'competition_id' => $this->competitionA->id,
            'competition_type' => 'WHOLE_COMPETITION',
            'round_from' => '2024-10-01',
            'round_to' => '2024-10-31',
            'round_hash' => 'foo_round_hash',
        ])->create();

        $participants = Participant::factory([
            'competition_id' => $this->competitionA->id,
            'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id,
            'competition_draw_id' => $competitionDraw->id
        ])
            ->count(30)
            ->create();

        $this->winningParticipant = $participants->get($this->winningPosition - 1);
    }

    public function test_winner_can_be_set()
    {
        $this->assertCount(30, Participant::where('competition_id', $this->competitionA->id)->get());
        $this->assertCount(0, CompetitionWinner::where('competition_id', $this->competitionA->id)->get());

        $this->post(
            route('competition.specify-winner', $this->competitionA), [
                'position' => $this->winningPosition,
                'round_hash' => 'foo_round_hash'
            ]
        )->assertCreated();

        $this->assertCount(1, $winner = CompetitionWinner::where('competition_id', $this->competitionA->id)->get());

        tap($winner->first(), function (CompetitionWinner $winner) {
            $this->assertSame($this->winningParticipant->id, $winner->participant_id);
            $this->assertSame($this->winningParticipant->competition_id, $winner->competition_id);
            $this->assertSame($this->winningParticipant->call_id, $winner->call_id);
            $this->assertSame(1, $winner->number_of_entries);
            $this->assertSame('foo_round_hash', $winner->round_hash);
            $this->assertSame($this->winningParticipant->competition_phone_line_id, $winner->phone_line_id);
            $this->assertSame('03000111111', $winner->competition_phone_number);
            $this->assertSame($this->winningParticipant->telephone, $winner->telephone);
            $this->assertSame($this->winningParticipant->call_start->format('Y-m-d H:i:s'), $winner->call_start);
            $this->assertSame($this->winningParticipant->call_end->format('Y-m-d H:i:s'), $winner->call_end);
        });
    }
}
