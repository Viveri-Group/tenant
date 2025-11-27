<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\FailedEntry;
use Illuminate\Database\Seeder;

class FailedEntrySeeder extends Seeder
{
    public function run()
    {
        $comp = Competition::where('name', 'Open Competition')->first();

        FailedEntry::factory()->create([
            'organisation_id' => $comp->organisation->id,
            'competition_id' => $comp->id,
            'phone_number' => $comp->phoneLines()->first()->phone_number,
        ]);
    }
}
