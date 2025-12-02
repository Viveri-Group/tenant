<?php

namespace Tests\Unit\Action\File;

use App\Action\File\AudioFileExistsCheckAction;
use App\Enums\CompetitionAudioType;
use App\Models\Competition;
use App\Models\FileUpload;
use Tests\TestCase;

class AudioFileExistsCheckActionTest extends TestCase
{
    public function test_competition_audio_exists()
    {
        $competition = Competition::factory()->create();

        FileUpload::factory()->create([
            'competition_id' => $competition->id,
            'type' => CompetitionAudioType::INTRO->name
        ]);

        $data = [
            'audio_type' => 'competition',
            'type' => CompetitionAudioType::INTRO->name
        ];

        $this->assertTrue((new AudioFileExistsCheckAction())->handle($data, $competition));
    }

    public function test_phone_line_audio_exists()
    {
        $competition = Competition::factory()->hasPhoneLines()->create();
        $phoneLineId = $competition->phoneLines->first()->id;

        FileUpload::factory()->create([
            'competition_phone_line_id' => $phoneLineId,
            'type' => CompetitionAudioType::INTRO->name
        ]);

        $data = [
            'audio_type' => 'competition_phone_line',
            'competition_phone_line_id' => $phoneLineId,
            'type' => CompetitionAudioType::INTRO->name
        ];

        $this->assertTrue((new AudioFileExistsCheckAction())->handle($data, $competition));
    }

    public function test_action_defaults_to_false()
    {
        $competition = Competition::factory()->create();

        $data = [
            'audio_type' => 'foo'
        ];

        $this->assertFalse((new AudioFileExistsCheckAction())->handle($data, $competition));
    }
}
