<?php

namespace Database\Seeders;

use App\Models\ActiveCall;
use App\Models\Competition;
use Illuminate\Database\Seeder;

class ActiveCallSeeder extends Seeder
{
    public function run()
    {
        $comp = Competition::where('name', 'Open Competition')->first();

        ActiveCall::factory()->create([
            'organisation_id' => $comp->organisation->id,
            'competition_id' => $comp->id,
            'competition_phone_line_id' => $comp->phoneLines()->first()->id,
        ]);
    }
}
