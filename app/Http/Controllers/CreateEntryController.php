<?php

namespace App\Http\Controllers;

use App\DTO\ActiveCall\ActiveCallDTO;
use App\Http\Requests\CreateEntryRequest;
use App\Jobs\CreateEntryJob;
use App\Models\ActiveCall;

class CreateEntryController extends Controller
{
    public function __invoke(CreateEntryRequest $request, ActiveCall $activeCall)
    {
        abort_if($activeCall->participant_id !== null, 409);

        CreateEntryJob::dispatchAfterResponse(
            new ActiveCallDTO(
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
                null,
                $activeCall->cli_presentation,
                null,
                $activeCall->created_at,
                $activeCall->updated_at
            )
        );

        return response(status: 200);
    }
}
