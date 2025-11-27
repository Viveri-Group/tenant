<?php

namespace Tests\Unit\Action\File;

use App\Action\File\GetCompetitionAudioAction;
use App\Enums\CompetitionAudioType;
use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\FileDefault;
use App\Models\FileUpload;
use Tests\TestCase;

class GetCompetitionAudioTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FileDefault::factory()->create(['external_id' => 1, 'type' => CompetitionAudioType::PRE_EVENT]);
        FileDefault::factory()->create(['external_id' => 2, 'type' => CompetitionAudioType::POST_EVENT]);
        FileDefault::factory()->create(['external_id' => 3, 'type' => CompetitionAudioType::FN1]);
        FileDefault::factory()->create(['external_id' => 4, 'type' => CompetitionAudioType::FN2]);
        FileDefault::factory()->create(['external_id' => 5, 'type' => CompetitionAudioType::FN3]);
        FileDefault::factory()->create(['external_id' => 6, 'type' => CompetitionAudioType::FN4]);
        FileDefault::factory()->create(['external_id' => 7, 'type' => CompetitionAudioType::FN5]);
        FileDefault::factory()->create(['external_id' => 8, 'type' => CompetitionAudioType::FN6]);
        FileDefault::factory()->create(['external_id' => 9, 'type' => CompetitionAudioType::CALL_COST_WARNING]);
        FileDefault::factory()->create(['external_id' => 10, 'type' => CompetitionAudioType::CAPPING_MESSAGE]);

        $this->competition = Competition::factory()
            ->hasPhoneLines(['phone_number' => '0333111111'])
            ->create();
    }

    public function test_phone_line_audio_is_set()
    {
        FileUpload::factory()->create([
            'external_id' => 999,
            'competition_id' => null,
            'competition_phone_line_id' => $this->competition->phoneLines()->first()->id,
            'type' => CompetitionAudioType::PRE_EVENT->name,
        ]);

        FileUpload::factory()->create([
            'external_id' => 2000,
            'competition_id' => null,
            'competition_phone_line_id' => $this->competition->phoneLines()->first()->id,
            'type' => CompetitionAudioType::POST_EVENT->name,
        ]);

        $audioFiles = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($this->competition->phoneLines()->first());

        $this->assertEqualsCanonicalizing(
            [
                "PRE_EVENT" => 999,
                "POST_EVENT" => 2000,
                "FN1" => 3,
                "FN2" => 4,
                "FN3" => 5,
                "FN4" => 6,
                "FN5" => 7,
                "FN6" => 8,
                "CALL_COST_WARNING" => 9,
                "CAPPING_MESSAGE" => 10,
            ], $audioFiles);
    }

    public function test_competition_audio_is_set()
    {
        FileUpload::factory()->create([
            'external_id' => 500,
            'competition_id' => $this->competition->id,
            'competition_phone_line_id' => null,
            'type' => CompetitionAudioType::PRE_EVENT->name,
        ]);

        FileUpload::factory()->create([
            'external_id' => 600,
            'competition_id' => $this->competition->id,
            'competition_phone_line_id' => null,
            'type' => CompetitionAudioType::POST_EVENT->name,
        ]);

        $audioFiles = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($this->competition->phoneLines()->first());

        $this->assertEqualsCanonicalizing(
            [
                "PRE_EVENT" => 500,
                "POST_EVENT" => 600,
                "FN1" => 3,
                "FN2" => 4,
                "FN3" => 5,
                "FN4" => 6,
                "FN5" => 7,
                "FN6" => 8,
                "CALL_COST_WARNING" => 9,
                "CAPPING_MESSAGE" => 10,
            ], $audioFiles);
    }

    public function test_default_audio_is_returned()
    {
        $audioFiles = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($this->competition->phoneLines()->first());

        $this->assertEqualsCanonicalizing(
            [
                "PRE_EVENT" => 1,
                "POST_EVENT" => 2,
                "FN1" => 3,
                "FN2" => 4,
                "FN3" => 5,
                "FN4" => 6,
                "FN5" => 7,
                "FN6" => 8,
                "CALL_COST_WARNING" => 9,
                "CAPPING_MESSAGE" => 10,
            ], $audioFiles);
    }

    public function test_phone_line_audio_is_prioritized_followed_by_competition_audio_and_default_is_last()
    {
        FileUpload::factory()->create([
            'external_id' => 100,
            'competition_id' => $this->competition->id,
            'competition_phone_line_id' => null,
            'type' => CompetitionAudioType::POST_EVENT->name,
        ]);

        FileUpload::factory()->create([
            'external_id' => 600,
            'competition_id' => $this->competition->id,
            'competition_phone_line_id' => $this->competition->phoneLines()->first()->id,
            'type' => CompetitionAudioType::POST_EVENT->name,
        ]);

        FileUpload::factory()->create([
            'external_id' => 500,
            'competition_id' => $this->competition->id,
            'competition_phone_line_id' => null,
            'type' => CompetitionAudioType::PRE_EVENT->name,
        ]);

        $audioFiles = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($this->competition->phoneLines()->first());

        $this->assertEqualsCanonicalizing(
            [
                "PRE_EVENT" => 500,
                "POST_EVENT" => 600,
                "FN1" => 3,
                "FN2" => 4,
                "FN3" => 5,
                "FN4" => 6,
                "FN5" => 7,
                "FN6" => 8,
                "CALL_COST_WARNING" => 9,
                "CAPPING_MESSAGE" => 10,
            ], $audioFiles);
    }

    public function test_phone_line_audio_for_another_line_doesnt_interfere_with_another_phone_lines_audio_settings()
    {
        CompetitionPhoneLine::factory([
            'competition_id' => $this->competition->id,
            'phone_number' => '033322222',
        ])->create();

        $phoneLines = $this->competition->phoneLines;

        FileUpload::factory()->create([
            'external_id' => 999,
            'competition_id' => null,
            'competition_phone_line_id' => $this->competition->phoneLines()->first()->id,
            'type' => CompetitionAudioType::PRE_EVENT->name,
        ]);

        FileUpload::factory()->create([
            'external_id' => 888,
            'competition_id' => null,
            'competition_phone_line_id' => $this->competition->phoneLines()->first()->id,
            'type' => CompetitionAudioType::FN1->name,
        ]);

        $audioFilesPhoneLineA = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($phoneLines->get(0));
        $audioFilesPhoneLineB = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($phoneLines->get(1));

        $this->assertEqualsCanonicalizing(
            [
                "PRE_EVENT" => 999,
                "POST_EVENT" => 2,
                "FN1" => 888,
                "FN2" => 4,
                "FN3" => 5,
                "FN4" => 6,
                "FN5" => 7,
                "FN6" => 8,
                "CALL_COST_WARNING" => 9,
                "CAPPING_MESSAGE" => 10,
            ],
            $audioFilesPhoneLineA
        );

        $this->assertEqualsCanonicalizing(
            [
                "PRE_EVENT" => 1,
                "POST_EVENT" => 2,
                "FN1" => 3,
                "FN2" => 4,
                "FN3" => 5,
                "FN4" => 6,
                "FN5" => 7,
                "FN6" => 8,
                "CALL_COST_WARNING" => 9,
                "CAPPING_MESSAGE" => 10,
            ],
            $audioFilesPhoneLineB
        );
    }

    public function test_competition_audio_applies_to_all_phone_lines_for_competition()
    {
        CompetitionPhoneLine::factory([
            'competition_id' => $this->competition->id,
            'phone_number' => '033322222',
        ])->create();

        $phoneLines = $this->competition->phoneLines;

        FileUpload::factory()->create([
            'external_id' => 999,
            'competition_id' => $this->competition->id,
            'competition_phone_line_id' => null,
            'type' => CompetitionAudioType::PRE_EVENT->name,
        ]);

        FileUpload::factory()->create([
            'external_id' => 888,
            'competition_id' => null,
            'competition_phone_line_id' => $this->competition->phoneLines()->first()->id,
            'type' => CompetitionAudioType::FN2->name,
        ]);

        $audioFilesPhoneLineA = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($phoneLines->get(0));
        $audioFilesPhoneLineB = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($phoneLines->get(1));

        $this->assertEqualsCanonicalizing(
            [
                "PRE_EVENT" => 999,
                "POST_EVENT" => 2,
                "FN1" => 3,
                "FN2" => 888,
                "FN3" => 5,
                "FN4" => 6,
                "FN5" => 7,
                "FN6" => 8,
                "CALL_COST_WARNING" => 9,
                "CAPPING_MESSAGE" => 10,
            ],
            $audioFilesPhoneLineA
        );

        $this->assertEqualsCanonicalizing(
            [
                "PRE_EVENT" => 999,
                "POST_EVENT" => 2,
                "FN1" => 3,
                "FN2" => 4,
                "FN3" => 5,
                "FN4" => 6,
                "FN5" => 7,
                "FN6" => 8,
                "CALL_COST_WARNING" => 9,
                "CAPPING_MESSAGE" => 10,
            ],
            $audioFilesPhoneLineB
        );
    }
}
