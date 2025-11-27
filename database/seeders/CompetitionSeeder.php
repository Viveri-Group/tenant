<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Organisation;
use Illuminate\Database\Seeder;

class CompetitionSeeder extends Seeder
{
    public function run(): void
    {
        $organisations = Organisation::all();
        $orgA = $organisations->get(0);
        $orgB = $organisations->get(1);

        Competition::factory()->create([
            'organisation_id' => $orgA->id,
            'name' => 'Closed Competition',
            'start' => now()->subDays(2),
            'end' => now()->subDay(),
        ]);

        Competition::factory()->create([
            'organisation_id' => $orgB->id,
            'name' => 'Open Competition',
            'start' => now()->subDay(),
            'end' => now()->addDays(7),
        ]);

        Competition::factory()->create([
            'organisation_id' => $orgA->id,
            'name' => 'Future Competition',
            'start' => now()->addDays(60),
            'end' => now()->addDays(67),
        ]);
    }
}
