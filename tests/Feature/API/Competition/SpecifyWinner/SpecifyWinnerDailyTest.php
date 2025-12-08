<?php

namespace Tests\Feature\API\Competition\SpecifyWinner;

use App\Models\Competition;
use App\Models\CompetitionDraw;
use App\Models\CompetitionWinner;
use App\Models\Participant;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SpecifyWinnerDailyTest extends TestCase
{
    public function setUp(): void
    {
        Carbon::setTestNow('2024-10-31 15:30:00');

        parent::setUp();

        $this->login();

        $this->winningPosition = ['round_one' => 1,'round_two' => 6,'round_three' => 5,'round_four' => 8];

        $this->competitionA = Competition::factory([
            'type' => 'DAILY',
            'start' => '2024-10-01 09:00:00',
            'end' => '2024-10-05 17:00:00',
            'active_from' => '15:10',
            'active_to' => '15:00',
        ])->hasPhoneLines(['phone_number' => '03000111111'])->create();

        $this->drawOne = CompetitionDraw::factory(['competition_id' => $this->competitionA->id, 'competition_type' => 'WEEKLY', 'round_from' => '2024-10-01', 'round_to' => '2024-10-08', 'round_hash' => 'round_one_hash',])->create();
        Participant::factory(['competition_id' => $this->competitionA->id, 'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id, 'call_start' => '2024-10-02 09:00:00', 'call_end' => '2024-10-02 09:00:57', 'competition_draw_id' => $this->drawOne->id])->count(10)->create();

        $this->drawTwo = CompetitionDraw::factory(['competition_id' => $this->competitionA->id, 'competition_type' => 'WEEKLY', 'round_from' => '2024-10-08', 'round_to' => '2024-10-15', 'round_hash' => 'round_two_hash',])->create();
        Participant::factory(['competition_id' => $this->competitionA->id, 'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id, 'call_start' => '2024-10-09 09:00:00', 'call_end' => '2024-10-02 09:00:55', 'competition_draw_id' => $this->drawTwo->id])->count(10)->create();

        $this->drawThree = CompetitionDraw::factory(['competition_id' => $this->competitionA->id, 'competition_type' => 'WEEKLY', 'round_from' => '2024-10-15', 'round_to' => '2024-10-22', 'round_hash' => 'round_three_hash',])->create();
        Participant::factory(['competition_id' => $this->competitionA->id, 'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id, 'call_start' => '2024-10-16 09:00:00', 'call_end' => '2024-10-02 09:00:52', 'competition_draw_id' => $this->drawThree->id])->count(10)->create();

        $this->drawFour = CompetitionDraw::factory(['competition_id' => $this->competitionA->id, 'competition_type' => 'WEEKLY', 'round_from' => '2024-10-22', 'round_to' => '2024-10-29', 'round_hash' => 'round_four_hash',])->create();
        Participant::factory(['competition_id' => $this->competitionA->id, 'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id, 'call_start' => '2024-10-22 09:00:00', 'call_end' => '2024-10-02 09:00:50', 'competition_draw_id' => $this->drawFour->id])->count(10)->create();

        $this->winningParticipantRoundOne = Participant::where('competition_draw_id', $this->drawOne->id)->skip($this->winningPosition['round_one'] - 1)->take(1)->first();
        $this->winningParticipantRoundTwo = Participant::where('competition_draw_id', $this->drawTwo->id)->skip($this->winningPosition['round_two'] - 1)->take(1)->first();
        $this->winningParticipantRoundThree = Participant::where('competition_draw_id', $this->drawThree->id)->skip($this->winningPosition['round_three'] - 1)->take(1)->first();
        $this->winningParticipantRoundFour = Participant::where('competition_draw_id', $this->drawFour->id)->skip($this->winningPosition['round_four'] - 1)->take(1)->first();
    }


    public function test_winner_can_be_set()
    {
        $this->assertCount(40, Participant::where('competition_id', $this->competitionA->id)->get());
        $this->assertCount(0, CompetitionWinner::where('competition_id', $this->competitionA->id)->get());

        $this->post(route('competition.specify-winner', $this->competitionA), ['position' => $this->winningPosition['round_one'], 'round_hash' => $this->drawOne->round_hash])->assertCreated();
        $this->post(route('competition.specify-winner', $this->competitionA), ['position' => $this->winningPosition['round_two'], 'round_hash' => $this->drawTwo->round_hash])->assertCreated();
        $this->post(route('competition.specify-winner', $this->competitionA), ['position' => $this->winningPosition['round_three'], 'round_hash' => $this->drawThree->round_hash])->assertCreated();
        $this->post(route('competition.specify-winner', $this->competitionA), ['position' => $this->winningPosition['round_four'], 'round_hash' => $this->drawFour->round_hash])->assertCreated();

        $this->assertCount(1, $winnerRoundOne = CompetitionWinner::where('round_hash', $this->drawOne->round_hash)->get());

        tap($winnerRoundOne->first(), function (CompetitionWinner $winner) {
            $this->assertSame($this->winningParticipantRoundOne->id, $winner->participant_id);
            $this->assertSame($this->winningParticipantRoundOne->competition_id, $winner->competition_id);
            $this->assertSame($this->winningParticipantRoundOne->call_id, $winner->call_id);
            $this->assertSame(1, $winner->number_of_entries);
            $this->assertSame($this->drawOne->round_hash, $winner->round_hash);
            $this->assertSame($this->winningParticipantRoundOne->competition_phone_line_id, $winner->phone_line_id);
            $this->assertSame('03000111111', $winner->competition_phone_number);
            $this->assertSame($this->winningParticipantRoundOne->telephone, $winner->telephone);
            $this->assertSame($this->winningParticipantRoundOne->call_start->format('Y-m-d H:i:s'), $winner->call_start );
            $this->assertSame($this->winningParticipantRoundOne->call_end->format('Y-m-d H:i:s'), $winner->call_end );
        });

        $this->assertCount(1, $winnerRoundTwo = CompetitionWinner::where('round_hash', $this->drawTwo->round_hash)->get());

        tap($winnerRoundTwo->first(), function (CompetitionWinner $winner) {
            $this->assertSame($this->winningParticipantRoundTwo->id, $winner->participant_id);
            $this->assertSame($this->winningParticipantRoundTwo->competition_id, $winner->competition_id);
            $this->assertSame($this->winningParticipantRoundTwo->call_id, $winner->call_id);
            $this->assertSame(1, $winner->number_of_entries);
            $this->assertSame($this->drawTwo->round_hash, $winner->round_hash);
            $this->assertSame($this->winningParticipantRoundTwo->competition_phone_line_id, $winner->phone_line_id);
            $this->assertSame('03000111111', $winner->competition_phone_number);
            $this->assertSame($this->winningParticipantRoundTwo->telephone, $winner->telephone);
            $this->assertSame($this->winningParticipantRoundTwo->call_start->format('Y-m-d H:i:s'), $winner->call_start);
            $this->assertSame($this->winningParticipantRoundTwo->call_end->format('Y-m-d H:i:s'), $winner->call_end);
        });

        $this->assertCount(1, $winnerRoundThree = CompetitionWinner::where('round_hash', $this->drawThree->round_hash)->get());

        tap($winnerRoundThree->first(), function (CompetitionWinner $winner) {
            $this->assertSame($this->winningParticipantRoundThree->id, $winner->participant_id);
            $this->assertSame($this->winningParticipantRoundThree->competition_id, $winner->competition_id);
            $this->assertSame($this->winningParticipantRoundThree->call_id, $winner->call_id);
            $this->assertSame(1, $winner->number_of_entries);
            $this->assertSame($this->drawThree->round_hash, $winner->round_hash);
            $this->assertSame($this->winningParticipantRoundThree->competition_phone_line_id, $winner->phone_line_id);
            $this->assertSame('03000111111', $winner->competition_phone_number);
            $this->assertSame($this->winningParticipantRoundThree->telephone, $winner->telephone);
            $this->assertSame($this->winningParticipantRoundThree->call_start->format('Y-m-d H:i:s'), $winner->call_start);
            $this->assertSame($this->winningParticipantRoundThree->call_end->format('Y-m-d H:i:s'), $winner->call_end);
        });

        $this->assertCount(1, $winnerRoundFour = CompetitionWinner::where('round_hash', $this->drawFour->round_hash)->get());

        tap($winnerRoundFour->first(), function (CompetitionWinner $winner) {
            $this->assertSame($this->winningParticipantRoundFour->id, $winner->participant_id);
            $this->assertSame($this->winningParticipantRoundFour->competition_id, $winner->competition_id);
            $this->assertSame($this->winningParticipantRoundFour->call_id, $winner->call_id);
            $this->assertSame(1, $winner->number_of_entries);
            $this->assertSame($this->drawFour->round_hash, $winner->round_hash);
            $this->assertSame($this->winningParticipantRoundFour->competition_phone_line_id, $winner->phone_line_id);
            $this->assertSame('03000111111', $winner->competition_phone_number);
            $this->assertSame($this->winningParticipantRoundFour->telephone, $winner->telephone);
            $this->assertSame($this->winningParticipantRoundFour->call_start->format('Y-m-d H:i:s'), $winner->call_start);
            $this->assertSame($this->winningParticipantRoundFour->call_end->format('Y-m-d H:i:s'), $winner->call_end);
        });
    }
}
