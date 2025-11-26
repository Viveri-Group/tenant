<?php

namespace Database\Seeders;

use App\Models\PhoneBookEntry;
use Illuminate\Database\Seeder;

class PhoneBookSeeder extends Seeder
{
    public function run(): void
    {
        $phoneBookEntry = [
             ['number'=>'448001111119','name'=>'FOO', 'organisation_id' => 1],
             ['number'=>'448002222223','name'=>'BAR', 'organisation_id' => 1],
             ['number'=>'448003333334','name'=>'BAZ', 'organisation_id' => 1],
             ['number'=>'448004444448','name'=>'BAT', 'organisation_id' => 1],

             ['number'=>'643529486214','name'=>'WOO', 'organisation_id' => 2],
             ['number'=>'203591696819','name'=>'GEE', 'organisation_id' => 2],
             ['number'=>'926458507126','name'=>'ROO', 'organisation_id' => 2],
        ];

        foreach ($phoneBookEntry as $entry) {
            PhoneBookEntry::factory()->create([
                'phone_number' => $entry['number'],
                'name' => $entry['name'],
                'organisation_id' => $entry['organisation_id'],
            ]);
        }
    }
}
