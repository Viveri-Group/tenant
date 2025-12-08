<?php

namespace Feature\API\Competition;

use App\Models\Competition;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CompetitionAssociateWithPhoneLineTest extends TestCase
{
    public function test_i_cannot_update_a_comp_that_conflicts_with_another_comps_phone_lines()
    {
        $this->login();

        $a = Competition::factory([
            'type' => 'WHOLE_COMPETITION',
            'start' => '2024-01-01 09:00:00',
            'end' => '2024-01-15 09:00:00',
        ])
            ->hasPhoneLines(1, ['phone_number' => '3333111222'])
            ->create();

        $secondaryCompetition = Competition::factory([
            'type' => 'WHOLE_COMPETITION',
            'start' => '2023-01-01 09:00:00',
            'end' => '2023-01-15 09:00:00',
        ])
            ->hasPhoneLines(1, ['phone_number' => '3333111222'])
            ->create();

        $this->post(route('competition.update', $secondaryCompetition), [
            'name' => 'foo',
            'start' => '2024-01-01 09:00:00',
            'end' => '2024-01-15 09:00:00',
            'type' => 'WHOLE_COMPETITION',
            'max_entries' => 1,
        ])->assertConflict();
    }
}
