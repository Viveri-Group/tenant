<?php

namespace Feature\API\Competition\SpecifyWinnerAlternative;

use App\Models\Competition;
use App\Models\CompetitionWinnerAlt;
use App\Models\CompetitionDraw;
use App\Models\CompetitionWinner;
use App\Models\Participant;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SpecifyWinnerAlternativeRetrospectiveTest extends TestCase
{
    public function test_winner_can_be_set_with_date_range_that_does_not_match_competition()
    {
        Carbon::setTestNow('2025-03-25 10:02:44');

        $this->login();

        list($organisation, $phoneBookEntry, $competition, $phoneLine, $competitionNumber, $callerNumber) = $this->setCompetition();

        Participant::factory([
            'competition_id' => $competition->id,
            'competition_phone_number'=> $phoneLine->phone_number,
            'call_start' => '2025-03-25 09:48:32',
            'call_end' => '2025-03-25 09:49:21',
            'call_id'=>555
        ])->create();

        $this->post(route('competition.specify-winner.alternative',$competition), [
            'position' => '1',
            'date_from' => '2025-03-25T07:53:00Z',
            'date_to' => '2025-03-25T10:02:00Z',
        ])->assertCreated()
            ->assertJson(function (AssertableJson $json) use($phoneLine) {
                return $json
                    ->where('data.type', 'competition-alternate-winner')
                    ->has('data.id')
                    ->has('data.attributes.participant_id')
                    ->has('data.attributes.competition_id')
                    ->where('data.attributes.call_id', 555)
                    ->where('data.attributes.number_of_entries', 1)
                    ->where('data.attributes.date_from', '2025-03-25 07:53:00')
                    ->where('data.attributes.date_to', '2025-03-25 10:02:00')
                    ->where('data.attributes.competition_phone_number', $phoneLine->phone_number)
                    ->where('data.attributes.call_start', '2025-03-25 09:48:32')
                    ->where('data.attributes.call_end', '2025-03-25 09:49:21')
                    ->has('data.attributes.telephone')

                    ->has('data.attributes.created_at');
            });
    }
}
