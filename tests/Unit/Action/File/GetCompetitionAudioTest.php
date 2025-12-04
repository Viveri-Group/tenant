<?php

namespace Tests\Unit\Action\File;

use App\Action\File\GetCompetitionAudioAction;
use App\Enums\CompetitionAudioType;
use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\FileDefault;
use App\Models\FileUpload;
use App\Models\Organisation;
use Tests\TestCase;

class GetCompetitionAudioTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        list($organisation, $phoneBookEntry, $competition, $phoneLine, $competitionNumber, $callerNumber) = $this->setCompetition();

        $this->organisation = $organisation;

        $this->organisationB = Organisation::factory()->create();

        $this->competition = $competition;

        $this->phoneLine = $phoneLine;

        FileDefault::factory()->create(['organisation_id' => $this->organisation->id, 'external_id' => 1, 'type' => CompetitionAudioType::INTRO->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisation->id, 'external_id' => 2, 'type' => CompetitionAudioType::CLI_READOUT_NOTICE->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisation->id, 'external_id' => 3, 'type' => CompetitionAudioType::DTMF_MENU->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisation->id, 'external_id' => 4, 'type' => CompetitionAudioType::DTMF_SUCCESS->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisation->id, 'external_id' => 5, 'type' => CompetitionAudioType::DTMF_SUCCESS_SMS->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisation->id, 'external_id' => 6, 'type' => CompetitionAudioType::DTMF_FAIL->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisation->id, 'external_id' => 7, 'type' => CompetitionAudioType::COMPETITION_CLOSED->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisation->id, 'external_id' => 8, 'type' => CompetitionAudioType::TOO_MANY_ENTRIES->name]);

        FileDefault::factory()->create(['organisation_id' => $this->organisationB->id, 'external_id' => 20, 'type' => CompetitionAudioType::INTRO->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisationB->id, 'external_id' => 21, 'type' => CompetitionAudioType::CLI_READOUT_NOTICE->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisationB->id, 'external_id' => 22, 'type' => CompetitionAudioType::DTMF_MENU->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisationB->id, 'external_id' => 23, 'type' => CompetitionAudioType::DTMF_SUCCESS->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisationB->id, 'external_id' => 24, 'type' => CompetitionAudioType::DTMF_FAIL->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisationB->id, 'external_id' => 25, 'type' => CompetitionAudioType::COMPETITION_CLOSED->name]);
        FileDefault::factory()->create(['organisation_id' => $this->organisationB->id, 'external_id' => 26, 'type' => CompetitionAudioType::TOO_MANY_ENTRIES->name]);

//        $this->competition = Competition::factory()
//            ->hasPhoneLines(['phone_number' => '0333111111'])
//            ->create();
    }

    public function test_phone_line_audio_is_set()
    {

        FileUpload::factory()->create([
            'external_id' => 999,
            'competition_id' => null,
            'competition_phone_line_id' => $this->phoneLine->id,
            'type' => CompetitionAudioType::INTRO->name,
        ]);

        FileUpload::factory()->create([
            'external_id' => 2000,
            'competition_id' => null,
            'competition_phone_line_id' => $this->phoneLine->id,
            'type' => CompetitionAudioType::DTMF_FAIL->name,
        ]);

        $audioFiles = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($this->phoneLine);

        $this->assertEqualsCanonicalizing(
            [
                "INTRO" => 999,
                "CLI_READOUT_NOTICE" => 2,
                "DTMF_MENU" => 3,
                "DTMF_SUCCESS" => 4,
                "DTMF_FAIL" => 2000,
                "COMPETITION_CLOSED" => 7,
                "TOO_MANY_ENTRIES" => 8,
            ], $audioFiles);
    }

    public function test_competition_audio_is_set()
    {
        FileUpload::factory()->create([
            'external_id' => 500,
            'competition_id' => $this->competition->id,
            'competition_phone_line_id' => null,
            'type' => CompetitionAudioType::INTRO->name,
        ]);

        FileUpload::factory()->create([
            'external_id' => 600,
            'competition_id' => $this->competition->id,
            'competition_phone_line_id' => null,
            'type' => CompetitionAudioType::DTMF_FAIL->name,
        ]);

        $audioFiles = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($this->phoneLine);

        $this->assertEqualsCanonicalizing(
            [
                "INTRO" => 500,
                "CLI_READOUT_NOTICE" => 2,
                "DTMF_MENU" => 3,
                "DTMF_SUCCESS" => 4,
                "DTMF_FAIL" => 600,
                "COMPETITION_CLOSED" => 7,
                "TOO_MANY_ENTRIES" => 8,
            ], $audioFiles);
    }

    public function test_default_audio_is_returned()
    {
        $audioFiles = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($this->phoneLine);

        $this->assertEqualsCanonicalizing(
            [
                "INTRO" => 1,
                "CLI_READOUT_NOTICE" => 2,
                "DTMF_MENU" => 3,
                "DTMF_SUCCESS" => 4,
                "DTMF_FAIL" => 6,
                "COMPETITION_CLOSED" => 7,
                "TOO_MANY_ENTRIES" => 8,
            ], $audioFiles);
    }

    public function test_phone_line_audio_is_prioritized_followed_by_competition_audio_and_default_is_last()
    {
        FileUpload::factory()->create([
            'external_id' => 100,
            'competition_id' => $this->competition->id,
            'competition_phone_line_id' => null,
            'type' => CompetitionAudioType::DTMF_FAIL->name,
        ]);

        FileUpload::factory()->create([
            'external_id' => 600,
            'competition_id' => $this->competition->id,
            'competition_phone_line_id' => $this->phoneLine->id,
            'type' => CompetitionAudioType::DTMF_FAIL->name,
        ]);

        FileUpload::factory()->create([
            'external_id' => 500,
            'competition_id' => $this->competition->id,
            'competition_phone_line_id' => null,
            'type' => CompetitionAudioType::INTRO->name,
        ]);

        $audioFiles = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($this->phoneLine);

        $this->assertEqualsCanonicalizing(
            [
                "INTRO" => 500,
                "CLI_READOUT_NOTICE" => 2,
                "DTMF_MENU" => 3,
                "DTMF_SUCCESS" => 4,
                "DTMF_FAIL" => 600,
                "COMPETITION_CLOSED" => 7,
                "TOO_MANY_ENTRIES" => 8,
            ], $audioFiles);
    }

    public function test_phone_line_audio_for_another_line_doesnt_interfere_with_another_phone_lines_audio_settings()
    {
        CompetitionPhoneLine::factory([
            'organisation_id' => $this->organisation->id,
            'competition_id' => $this->competition->id,
            'phone_number' => '033322222',
        ])->create();

        $phoneLines = $this->competition->phoneLines;

        FileUpload::factory()->create([
            'external_id' => 999,
            'competition_id' => null,
            'competition_phone_line_id' => $this->phoneLine->id,
            'type' => CompetitionAudioType::INTRO->name,
        ]);

        FileUpload::factory()->create([
            'external_id' => 888,
            'competition_id' => null,
            'competition_phone_line_id' => $this->phoneLine->id,
            'type' => CompetitionAudioType::COMPETITION_CLOSED->name,
        ]);

        $audioFilesPhoneLineA = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($phoneLines->get(0));
        $audioFilesPhoneLineB = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($phoneLines->get(1));

        $this->assertEqualsCanonicalizing(
            [
                "INTRO" => 999,
                "CLI_READOUT_NOTICE" => 2,
                "DTMF_MENU" => 3,
                "DTMF_SUCCESS" => 4,
                "DTMF_FAIL" => 6,
                "COMPETITION_CLOSED" => 888,
                "TOO_MANY_ENTRIES" => 8,
            ],
            $audioFilesPhoneLineA
        );

        $this->assertEqualsCanonicalizing(
            [
                "INTRO" => 1,
                "CLI_READOUT_NOTICE" => 2,
                "DTMF_MENU" => 3,
                "DTMF_SUCCESS" => 4,
                "DTMF_FAIL" => 6,
                "COMPETITION_CLOSED" => 7,
                "TOO_MANY_ENTRIES" => 8,
            ],
            $audioFilesPhoneLineB
        );
    }

    public function test_competition_audio_applies_to_all_phone_lines_for_competition()
    {
        CompetitionPhoneLine::factory([
            'organisation_id' => $this->organisation->id,
            'competition_id' => $this->competition->id,
            'phone_number' => '033322222',
        ])->create();

        $phoneLines = $this->competition->phoneLines;

        FileUpload::factory()->create([
            'external_id' => 999,
            'competition_id' => $this->competition->id,
            'competition_phone_line_id' => null,
            'type' => CompetitionAudioType::INTRO->name,
        ]);

        FileUpload::factory()->create([
            'external_id' => 888,
            'competition_id' => null,
            'competition_phone_line_id' => $this->phoneLine->id,
            'type' => CompetitionAudioType::COMPETITION_CLOSED->name,
        ]);

        $audioFilesPhoneLineA = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($phoneLines->get(0));
        $audioFilesPhoneLineB = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($phoneLines->get(1));

        $this->assertEqualsCanonicalizing(
            [
                "INTRO" => 999,
                "CLI_READOUT_NOTICE" => 2,
                "DTMF_MENU" => 3,
                "DTMF_SUCCESS" => 4,
                "DTMF_FAIL" => 6,
                "COMPETITION_CLOSED" => 888,
                "TOO_MANY_ENTRIES" => 8,
            ],
            $audioFilesPhoneLineA
        );

        $this->assertEqualsCanonicalizing(
            [
                "INTRO" => 999,
                "CLI_READOUT_NOTICE" => 2,
                "DTMF_MENU" => 3,
                "DTMF_SUCCESS" => 4,
                "DTMF_FAIL" => 6,
                "COMPETITION_CLOSED" => 7,
                "TOO_MANY_ENTRIES" => 8,
            ],
            $audioFilesPhoneLineB
        );
    }
}
