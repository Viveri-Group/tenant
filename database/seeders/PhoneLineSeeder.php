<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\Organisation;
use Illuminate\Database\Seeder;

class PhoneLineSeeder extends Seeder
{
    public function run(): void
    {
        $organisations = Organisation::all();
        $orgA = $organisations->get(0);
        $orgB = $organisations->get(1);

        $competitions = Competition::all();

        //active competition
        CompetitionPhoneLine::factory()->create(['competition_id' => $competitions->get(0)->id, 'phone_number' => '448001111119', 'organisation_id' => $orgA->id]);
        CompetitionPhoneLine::factory()->create(['competition_id' => $competitions->get(0)->id, 'phone_number' => '448002222223', 'organisation_id' => $orgA->id]);

        //closed competition
        CompetitionPhoneLine::factory()->create(['competition_id' => $competitions->get(1)->id, 'phone_number' => '448003333334', 'organisation_id' => $orgB->id]);
        CompetitionPhoneLine::factory()->create(['competition_id' => $competitions->get(1)->id, 'phone_number' => '448004444448', 'organisation_id' => $orgB->id]);

        //future competition
        CompetitionPhoneLine::factory()->create(['competition_id' => $competitions->get(2)->id, 'phone_number' => '643529486214', 'organisation_id' => $orgA->id]);
        CompetitionPhoneLine::factory()->create(['competition_id' => $competitions->get(2)->id, 'phone_number' => '203591696819', 'organisation_id' => $orgA->id]);
    }
}
