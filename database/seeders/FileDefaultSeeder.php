<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\FileDefault;
use App\Models\Organisation;
use Illuminate\Database\Seeder;

class FileDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $organisations = Organisation::all();
        $orgA = $organisations->get(0);
        $orgB = $organisations->get(1);

        FileDefault::factory(['organisation_id' => $orgA->id, 'type' => 'INTRO', 'external_id' => 1, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();
        FileDefault::factory(['organisation_id' => $orgA->id, 'type' => 'CLI_READOUT_NOTICE', 'external_id' => 2, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();
        FileDefault::factory(['organisation_id' => $orgA->id, 'type' => 'DTMF_MENU', 'external_id' => 3, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();
        FileDefault::factory(['organisation_id' => $orgA->id, 'type' => 'DTMF_SUCCESS', 'external_id' => 4, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();
        FileDefault::factory(['organisation_id' => $orgA->id, 'type' => 'DTMF_FAIL', 'external_id' => 5, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();
        FileDefault::factory(['organisation_id' => $orgA->id, 'type' => 'COMPETITION_CLOSED', 'external_id' => 6, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();
        FileDefault::factory(['organisation_id' => $orgA->id, 'type' => 'TOO_MANY_ENTRIES', 'external_id' => 7, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();

        FileDefault::factory(['organisation_id' => $orgB->id, 'type' => 'INTRO', 'external_id' => 30, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();
        FileDefault::factory(['organisation_id' => $orgB->id, 'type' => 'CLI_READOUT_NOTICE', 'external_id' => 31, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();
        FileDefault::factory(['organisation_id' => $orgB->id, 'type' => 'DTMF_MENU', 'external_id' => 32, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();
        FileDefault::factory(['organisation_id' => $orgB->id, 'type' => 'DTMF_SUCCESS', 'external_id' => 33, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();
        FileDefault::factory(['organisation_id' => $orgB->id, 'type' => 'DTMF_FAIL', 'external_id' => 34, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();
        FileDefault::factory(['organisation_id' => $orgB->id, 'type' => 'COMPETITION_CLOSED', 'external_id' => 35, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();
        FileDefault::factory(['organisation_id' => $orgB->id, 'type' => 'TOO_MANY_ENTRIES', 'external_id' => 36, 'filename' => null, 'mime_type' => null, 'extension' => null])->create();


    }
}
