<?php

namespace App\Action\Competition;

use App\Action\EntrantRoundCount\GetEntrantRoundCountAction;
use App\DTO\Competition\CompetitionPreCheckRequestDTO;
use App\Enums\CompetitionStatusEnum;
use App\Enums\ResponseStatus;
use App\Exceptions\CompetitionClosedHTTPException;
use App\Exceptions\NoActiveCompetitionButCompetitionNumberKnownHTTPException;
use App\Exceptions\NoActiveCompetitionsHTTPException;
use App\Http\Resources\CompetitionCapacityCheckResource;
use App\Http\Resources\CompetitionCapacityCheckWithActivePhoneLineResource;
use App\Http\Resources\CompetitionResource;
use App\Models\ActiveCall;
use App\Models\CompetitionPhoneLine;
use Throwable;

class CompetitionPreCheckAction
{
    /**
     * @throws Throwable
     */
    public function handle(
        CompetitionPreCheckRequestDTO $requestDetails,
        ?CompetitionPhoneLine         $phoneLine,
        string                        $responseType,
                                      $recordActiveCall = true
    ): CompetitionResource|CompetitionCapacityCheckResource|CompetitionCapacityCheckWithActivePhoneLineResource
    {
        $activeCall = null;
        $entriesCount = null;
        $callerNumber = $requestDetails->callerPhoneNumber;

        throw_if(
            !$phoneLine,
            new NoActiveCompetitionsHTTPException(
                400,
                'No competitions associated with this phone line.',
                [
                    'competition_id' => null,
                    'status' => ResponseStatus::REJECT_CALLER->value,
                    'active_call_id' => null,
                ])
        );

        throw_if(
            $phoneLine->competition->isPreOpen,
            new NoActiveCompetitionButCompetitionNumberKnownHTTPException(
                410,
                'Competition is not yet open',
                [
                    'competition_id' => $phoneLine->competition->id,
                    'status' => ResponseStatus::PRE_OPEN->value,
                    'active_call_id' => null,
                ])
        );

        $activePhoneLine = $phoneLine;
        $activeCompetition = $activePhoneLine->competition;

        throw_if(
            $activeCompetition->isClosed,
            new CompetitionClosedHTTPException(
                411,
                'Competition is closed',
                [
                    'competition' => $activeCompetition,
                    'active_phone_line' => $activePhoneLine,
                    'status' => ResponseStatus::CLOSED->value,
                    'active_call_id' => null,
                ])
        );

        if ($recordActiveCall) {
            $activeCall = ActiveCall::create([
                'organisation_id' => $phoneLine->organisation_id,
                'competition_id' => $phoneLine->competition->id,
                'competition_phone_line_id' => $phoneLine->id,
                'call_id' => $requestDetails->callID,
                'phone_number' => $requestDetails->competitionPhoneNumber,
                'caller_phone_number' => $callerNumber,
                'cli_presentation' => $requestDetails->cliPresentation,
                'status' => CompetitionStatusEnum::OPEN_PRE_ANSWER->value,
            ]);
        }

        if ($recordActiveCall) {
            $entriesCount = (new GetEntrantRoundCountAction())->handle($activeCall);
        }

        return match ($responseType) {
            CompetitionCapacityCheckResource::class => new CompetitionCapacityCheckResource($activeCompetition, ['status' => 'OPEN', 'active_call_id' => $activeCall?->id, 'entry_count' => $entriesCount]),
            CompetitionCapacityCheckWithActivePhoneLineResource::class => new CompetitionCapacityCheckWithActivePhoneLineResource($activePhoneLine, ['status' => 'OPEN', 'active_call_id' => $activeCall?->id, 'entry_count' => $entriesCount]),
            default => new CompetitionResource($activeCompetition),
        };
    }
}
