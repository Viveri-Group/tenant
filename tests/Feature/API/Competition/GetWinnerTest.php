<?php

namespace Tests\Feature\API\Competition;

use App\Models\Competition;
use App\Models\CompetitionDraw;
use App\Models\CompetitionWinner;
use App\Models\Participant;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class GetWinnerTest extends TestCase
{
    public function setUp():void
    {
        parent::setUp();

        $this->login();

        $this->competitionA = Competition::factory(['name' => 'Test Competition'])->hasPhoneLines(['phone_number' => '03000111111'])->create();
        $this->draw = CompetitionDraw::factory()->create(['competition_id' => $this->competitionA->id, 'round_hash'=>'round_hash_foo', 'round_from' => '2024-01-01', 'round_to' => '2024-01-10']);
        $participants = Participant::factory(['competition_id' => $this->competitionA->id, 'competition_phone_line_id' => $this->competitionA->phoneLines()->first()->id])->count(30)->create();

        $this->winningParticipant = $participants->get(5);

        $this->winner = CompetitionWinner::factory()->create([
            'participant_id' => $this->winningParticipant->id,
            'competition_id' => $this->winningParticipant->competition_id,
            'round_hash' => $this->draw->round_hash,
            'phone_line_id' => $this->winningParticipant->competition_phone_line_id,
            'telephone' => $this->winningParticipant->telephone,
        ]);
    }

    public function test_winner_can_be_retrieved()
    {
        $this->get(route('competition.get-winner', $this->competitionA))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('data.type', 'competition-winner')
                    ->where('data.id', $this->winner->id)
                    ->where('data.attributes.participant_id', $this->winner->participant_id)
                    ->where('data.attributes.competition_id', $this->winner->competition_id)
                    ->where('data.attributes.call_id', $this->winner->call_id)
                    ->where('data.attributes.number_of_entries', $this->winner->number_of_entries)
                    ->where('data.attributes.round_hash', $this->winner->round_hash)
                    ->where('data.attributes.round_from', $this->draw->round_from)
                    ->where('data.attributes.round_to', $this->draw->round_to)
                    ->where('data.attributes.phone_line_id', $this->winner->phone_line_id)
                    ->where('data.attributes.competition_phone_number', $this->winner->competition_phone_number)
                    ->where('data.attributes.telephone', $this->winner->telephone)
                    ->where('data.attributes.call_start', $this->winner->call_start->format('Y-m-d H:i:s'))
                    ->has('data.attributes.created_at');
            });
    }
}
