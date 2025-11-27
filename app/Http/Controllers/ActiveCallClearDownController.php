<?php

namespace App\Http\Controllers;

use App\DTO\ActiveCall\ActiveCallDTO;
use App\Enums\CompetitionStatusEnum;
use App\Http\Requests\ActiveCallClearDownRequest;
use App\Jobs\HandleCompetitionClearDownSuccessJob;
use App\Jobs\HandleCompetitionFailClearDownJob;
use App\Models\ActiveCall;

class ActiveCallClearDownController extends Controller
{
    public function __invoke(ActiveCallClearDownRequest $request, ActiveCall $activeCall)
    {
        $activeCallDTO = new ActiveCallDTO(
            $activeCall->id,
            $activeCall->organisation_id,
            $activeCall->competition_id,
            $activeCall->call_id,
            $activeCall->participant_id,
            $activeCall->competition_phone_line_id,
            $activeCall->phone_number,
            $activeCall->caller_phone_number,
            $activeCall->status,
            $activeCall->round_start,
            $activeCall->round_end,
            now(),
            $activeCall->cli_presentation,
            $request->input('recordFileNum'),
            $activeCall->created_at,
            $activeCall->updated_at
        );

        match($request->input('marker')){
            CompetitionStatusEnum::COMP_OPEN_ANSWERED->value,
            CompetitionStatusEnum::COMP_OPEN_RECORDING->value,
            CompetitionStatusEnum::COMP_OPEN_COMPLETE->value,
            CompetitionStatusEnum::EARLY_HANGUP_COMP_OPEN_RECORDING->value,

            CompetitionStatusEnum::ABORTED_COMP_OPEN_RECORDING->value,
            CompetitionStatusEnum::ABORTED_COMP_OPEN_ANSWERED->value,

            CompetitionStatusEnum::EARLY_HANGUP_COMP_OPEN_ANSWERED->value => HandleCompetitionClearDownSuccessJob::dispatchAfterResponse($activeCallDTO),

            default => HandleCompetitionFailClearDownJob::dispatchAfterResponse($activeCallDTO, $request->input('marker')),
        };

        return response(status: 200);
    }
}
