<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Organisation;
use Illuminate\Database\Seeder;

class OrganisationSeeder extends Seeder
{
    public function run(): void
    {
        Organisation::factory()->create([
            'name' => 'Organisation A',
        ]);

        Organisation::factory()->create([
            'name' => 'Organisation B',
        ]);
    }
}
