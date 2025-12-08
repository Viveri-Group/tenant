<?php

namespace Tests\Feature\API\Competition\SpecifyWinner;

use App\Models\Competition;
use App\Models\CompetitionDraw;
use App\Models\CompetitionWinner;
use App\Models\Participant;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SpecifyWinnerValidationTest extends TestCase
{
    public function test_position_has_to_be_greater_or_equal_to_one()
    {
        $this->login();

        $competition = Competition::factory()->create();

        $this->post(
            route('competition.specify-winner', $competition), [
                'position' => 0,
                'round_hash' => 'foo_round_hash'
            ]
        )->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->has('message')
                    ->where('data.0.message', 'The position field must be greater than or equal to 1.')
                    ->where('data.0.source', 'position');
            });;
    }

    public function test_hash_cannot_be_found()
    {
        $this->login();

        $competition = Competition::factory()->create();

        $this->post(
            route('competition.specify-winner', $competition), [
                'position' => 5,
                'round_hash' => 'unknown_hash'
            ]
        )->assertUnauthorized();
    }

    public function test_can_assign_more_than_one_winner()
    {
        $this->login();

        $competition = Competition::factory()->hasPhoneLines(['phone_number' => '03000111111'])->create();
        $draw = CompetitionDraw::factory(['competition_id' => $competition->id, 'competition_type' => 'WEEKLY', 'round_from' => '2024-10-01', 'round_to' => '2024-10-08', 'round_hash' => 'round_one_hash',])->create();

        $participants = Participant::factory()->count(3)->create(['competition_id' => $competition->id, 'competition_draw_id' => $draw->id, 'competition_phone_line_id' => $competition->phoneLines->first()->id]);

        $this->post(
            route('competition.specify-winner', $competition), [
                'position' => 1,
                'round_hash' => $draw->round_hash
            ]
        )->assertCreated();

        $this->post(
            route('competition.specify-winner', $competition), [
                'position' => 2,
                'round_hash' => $draw->round_hash
            ]
        )->assertCreated();

        $this->post(
            route('competition.specify-winner', $competition), [
                'position' => 3,
                'round_hash' => $draw->round_hash
            ]
        )->assertCreated();

        $this->assertCount(3, CompetitionWinner::all());

        tap(CompetitionWinner::all(), function (Collection $competitionWinners) use($draw, $competition, $participants) {
            $participantOne = $participants->get(0);
            $winnerOne = $competitionWinners->get(0);
            $this->assertSame($participantOne->id, $winnerOne->participant_id);
            $this->assertSame($competition->id, $winnerOne->competition_id);
            $this->assertSame($participantOne->call_id, $winnerOne->call_id);
            $this->assertSame(1, $winnerOne->number_of_entries);
            $this->assertSame($draw->round_hash, $winnerOne->round_hash);
            $this->assertSame($participantOne->competition_phone_line_id, $winnerOne->phone_line_id);
            $this->assertSame('03000111111', $winnerOne->competition_phone_number);
            $this->assertSame($participantOne->telephone, $winnerOne->telephone);
            $this->assertSame($participantOne->call_start->format('Y-m-d H:i:s'), $winnerOne->call_start);
            $this->assertSame($participantOne->call_end->format('Y-m-d H:i:s'), $winnerOne->call_end);

            $participantTwo = $participants->get(1);
            $winnerTwo = $competitionWinners->get(1);
            $this->assertSame($participantTwo->id, $winnerTwo->participant_id);
            $this->assertSame($draw->round_hash, $winnerTwo->round_hash);
            $this->assertSame($competition->id, $winnerTwo->competition_id);
            $this->assertSame($participantTwo->call_id, $winnerTwo->call_id);
            $this->assertSame(1, $winnerTwo->number_of_entries);
            $this->assertSame($draw->round_hash, $winnerTwo->round_hash);
            $this->assertSame($participantTwo->competition_phone_line_id, $winnerTwo->phone_line_id);
            $this->assertSame('03000111111', $winnerTwo->competition_phone_number);
            $this->assertSame($participantTwo->telephone, $winnerTwo->telephone);
            $this->assertSame($participantTwo->call_start->format('Y-m-d H:i:s'), $winnerTwo->call_start);
            $this->assertSame($participantTwo->call_end->format('Y-m-d H:i:s'), $winnerTwo->call_end);

            $participantThree = $participants->get(2);
            $winnerThree = $competitionWinners->get(2);
            $this->assertSame($participantThree->id, $winnerThree->participant_id);
            $this->assertSame($draw->round_hash, $winnerThree->round_hash);
            $this->assertSame($competition->id, $winnerThree->competition_id);
            $this->assertSame($participantThree->call_id, $winnerThree->call_id);
            $this->assertSame(1, $winnerThree->number_of_entries);
            $this->assertSame($draw->round_hash, $winnerThree->round_hash);
            $this->assertSame($participantThree->competition_phone_line_id, $winnerThree->phone_line_id);
            $this->assertSame('03000111111', $winnerThree->competition_phone_number);
            $this->assertSame($participantThree->telephone, $winnerThree->telephone);
            $this->assertSame($participantThree->call_start->format('Y-m-d H:i:s'), $winnerThree->call_start);
            $this->assertSame($participantThree->call_end->format('Y-m-d H:i:s'), $winnerThree->call_end);
        });
    }
}
