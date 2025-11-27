<?php

namespace Database\Seeders;

use App\Models\ActiveCallOrphan;
use App\Models\Competition;
use Illuminate\Database\Seeder;

class OrphanCallSeeder extends Seeder
{
    public function run()
    {
        $comp = Competition::where('name', 'Open Competition')->first();

        ActiveCallOrphan::factory()->create([
            'organisation_id' => $comp->organisation->id,
            'competition_id' => $comp->id,
            'phone_number' => $comp->phoneLines()->first()->phone_number,
            'original_call_time' => now()->subMinutes(10),
        ]);
    }
}
