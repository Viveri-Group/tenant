<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OrganisationSeeder::class);
        $this->call(PhoneBookSeeder::class);
//        $this->call(CompetitionSeeder::class);
//        $this->call(PhoneLineSeeder::class);
//        $this->call(ParticipantSeeder::class);
//        $this->call(FileUploadSeeder::class);
//        $this->call(FileDefaultSeeder::class);
        $this->call(UserSeeder::class);
    }
}
