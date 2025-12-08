<?php

namespace Tests\Feature\API\Competition;

use App\Models\Competition;
use App\Models\CompetitionDraw;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class GetCompetitionDrawsTest extends TestCase
{
    public function test_can_get_draws()
    {
        $this->login();

        $competition = Competition::factory(['type'=>'WEEKLY'])->create();
        $drawOne = CompetitionDraw::factory()->create(['competition_id' => $competition->id, 'round_hash' => 'round_1', 'competition_type'=>'WEEKLY']);
        $drawTwo = CompetitionDraw::factory()->create(['competition_id' => $competition->id, 'round_hash' => 'round_2', 'competition_type'=>'WEEKLY']);

        $this->get(route('competition.get-draws', [$competition]))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use($competition, $drawOne, $drawTwo) {
                return $json
                    ->where('data.0.type', 'competition-draw')
                    ->where('data.0.attributes.competition_id', $competition->id)
                    ->where('data.0.attributes.competition_type', $competition->type)
                    ->where('data.0.attributes.round_from', $drawOne->round_from)
                    ->where('data.0.attributes.round_to', $drawOne->round_to)
                    ->where('data.0.attributes.round_hash', $drawOne->round_hash)
                    ->has('data.0.attributes.drawn_by')

                    ->where('data.1.type', 'competition-draw')
                    ->where('data.1.attributes.competition_id', $competition->id)
                    ->where('data.1.attributes.competition_type', $competition->type)
                    ->where('data.1.attributes.round_from', $drawTwo->round_from)
                    ->where('data.1.attributes.round_to', $drawTwo->round_to)
                    ->where('data.1.attributes.round_hash', $drawTwo->round_hash);
            });
    }

    public function test_no_draws_shows_as_empty()
    {
        $this->login();

        $competition = Competition::factory(['type'=>'WEEKLY'])->create();

        $this->get(route('competition.get-draws', [$competition]))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use($competition) {
                return $json
                    ->where('data', []);
            });
    }
}
