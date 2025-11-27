<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);

        $this->call(OrganisationSeeder::class);
        $this->call(PhoneBookSeeder::class);
        $this->call(CompetitionSeeder::class);
        $this->call(PhoneLineSeeder::class);
        $this->call(ParticipantSeeder::class);
        $this->call(ActiveCallSeeder::class);
        $this->call(OrphanCallSeeder::class);
        $this->call(FailedEntrySeeder::class);
//        $this->call(FileUploadSeeder::class);
//        $this->call(FileDefaultSeeder::class);
    }
}
