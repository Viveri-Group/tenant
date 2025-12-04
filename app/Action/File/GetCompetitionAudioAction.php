<?php

namespace App\Action\File;

use App\Models\Competition;
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
        $audioFiles = (new GetCompetitionDefaultAudioAction($competitionPhoneLine->organisation_id, $this->expectedFileTypes))->handle($audioFiles);

        // swap out DTMF_SUCCESS with DTMF_SUCCESS_SMS audio if applicable
        // todo issue #180
        return $this->handleDTMFSuccessSMSAudio($competitionPhoneLine->competition, $audioFiles);
    }

    protected function handleDTMFSuccessSMSAudio(Competition $competition, array $audioFiles): array
    {
        if (array_key_exists('DTMF_SUCCESS_SMS', $audioFiles)) {
            if($competition->sms_offer_enabled) {
                $audioFiles['DTMF_SUCCESS'] = $audioFiles['DTMF_SUCCESS_SMS'];
            }

            unset($audioFiles['DTMF_SUCCESS_SMS']);
        }

        return $audioFiles;
    }
}
