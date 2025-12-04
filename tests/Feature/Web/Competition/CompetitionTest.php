<?php

namespace Tests\Feature\Web\Competition;

use App\Models\Competition;
use Tests\TestCase;

class CompetitionTest extends TestCase
{
    public function test_page_redirects_as_expected()
    {
        $this->login();

        $this->get(route('web.competition.index'))
            ->assertStatus(302);
    }

    public function test_page_shows_as_expected()
    {
        $this->login();

        list($organisation, $phoneBookEntry, $competition, $phoneLine, $competitionNumber, $callerNumber) = $this->setCompetition();

        $this->get(route('web.competition.index',
            [
                'date_from' => now()->startOfMonth()->subMonth()->format('Y-m-d\TH:i:s'),
                'date_to' => now()->endOfDay()->format('Y-m-d\TH:i:s')
            ]))
            ->assertOk()
            ->assertSee($competition->name);
    }

    public function test_competition_search_works_as_expected()
    {
        $this->login();

        $competitionA = Competition::factory(['start'=>'2024-01-01 13:00:00'])->create();
        $competitionB = Competition::factory(['start'=>'2023-01-01 13:00:00'])->create();

        $this->get(route('web.competition.index',
            [
                'date_from' => '2024-01-01 12:00:00',
                'date_to' => '2024-01-01 14:00:00',
            ]))
            ->assertOk()
            ->assertSee($competitionA->name)
            ->assertDontSee($competitionB->name);
    }

    public function test_name_search_works_as_expected()
    {
        $this->login();

        $competitionA = Competition::factory()->create();
        $competitionB = Competition::factory()->create();

        $this->get(route('web.competition.index',
            [
                'name' => $competitionA->name
            ]))
            ->assertOk()
            ->assertSee($competitionA->name)
            ->assertDontSee($competitionB->name);
    }
}
