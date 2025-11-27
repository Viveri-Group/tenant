<?php

namespace App\Action\File;

use App\Models\CompetitionPhoneLine;

class GetCompetitionAudioAction
{
    public function __construct(public array $expectedFileTypes)
    {
    }

    public function handle(CompetitionPhoneLine $competitionPhoneLine): array
    {
        $competitionPhoneLine->load('files', 'competition.files');

        // get competition phone line audio first
        $audioFiles = (new FormatAudioAction($this->expectedFileTypes))->handle([], $competitionPhoneLine->files);

        // get competition audio next
        $audioFiles = (new FormatAudioAction($this->expectedFileTypes))->handle($audioFiles, $competitionPhoneLine->competition->files);

        // add in default audio where missing
        return (new GetCompetitionDefaultAudioAction($this->expectedFileTypes))->handle($audioFiles);
    }
}
