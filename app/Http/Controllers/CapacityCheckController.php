<?php

namespace App\Http\Controllers;

use App\Action\CapacityCheck\MaxLinesExceeded;
use App\Action\Competition\CapacityCheckExceptionHandlerAction;
use App\Action\Competition\CompetitionPreCheckAction;
use App\Action\Organisation\GetOrganisationAction;
use App\Action\PhoneLine\PhoneNumberCleanupAction;
use App\DTO\Competition\CompetitionPreCheckRequestDTO;
use App\Enums\ResponseStatus;
use App\Exceptions\CustomCompetitionHttpException;
use App\Http\Resources\CompetitionCapacityCheckWithActivePhoneLineResource;
use App\Jobs\UpdateActiveCallJob;
use App\Models\ActiveCall;
use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\MaxCapacityCallLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CapacityCheckController extends Controller
{
    protected string $callerPhoneNumber;

    protected string $competitionPhoneNumber;

    protected int $httpResponseCode = 200;

    protected string $httpResponseStatus = 'OPEN';

    public function __invoke(Request $request)
    {
        $this->callerPhoneNumber = (new PhoneNumberCleanupAction())->handle($request->input('caller_phone_number'));
        $this->competitionPhoneNumber = (new PhoneNumberCleanupAction())->handle($request->input('phone_number'));

        $phoneLine = Cache::remember(
            "competition_phone_line_{$this->competitionPhoneNumber}",
            60, // seconds
            function () {
                return CompetitionPhoneLine::where('phone_number', $this->competitionPhoneNumber)->first();
            }
        );

        abort_if($phoneLine?->competition && $this->maxNumberOfLinesExceeded($request, $phoneLine), 412, 'Active lines allowance exceeded.');

        try {
            $response = (new CompetitionPreCheckAction())->handle(
                new CompetitionPreCheckRequestDTO(
                    $this->callerPhoneNumber,
                    $this->competitionPhoneNumber,
                    $request->input('call_id')
                ),
                $phoneLine,
                CompetitionCapacityCheckWithActivePhoneLineResource::class
            );
        } catch (CustomCompetitionHttpException $e) {
            list($this->httpResponseCode, $this->httpResponseStatus, $errorType, $response) = (new CapacityCheckExceptionHandlerAction())->handle($e);
        }

        $this->handleUpdateActiveCallJob($request, $response);

        return response()->json($response, $this->httpResponseCode);
    }

    protected function maxNumberOfLinesExceeded(Request $request, CompetitionPhoneLine $phoneLine): bool
    {
        $organisation = (new GetOrganisationAction())->handle($phoneLine->organisation_id);

        if(!$organisation->max_number_of_lines || $organisation->max_number_of_lines < 0) {
            return false;
        }

        $orgLinesInUse = ActiveCall::where('organisation_id', $phoneLine->organisation_id)->count();

        $linesHaveBeenExceeded = (new MaxLinesExceeded())->handle($phoneLine->organisation_id, $orgLinesInUse);

        if ($linesHaveBeenExceeded) {
            MaxCapacityCallLog::create([
                'call_id' => $request->input('call_id'),
                'allowed_capacity' => $organisation->max_number_of_lines
            ]);
        }

        return $linesHaveBeenExceeded;
    }

    protected function handleUpdateActiveCallJob(Request $request, $response):void
    {
        if( !in_array($this->httpResponseStatus, [ResponseStatus::TOO_MANY->value, ResponseStatus::REJECT_CALLER->value])) {
            UpdateActiveCallJob::dispatch($response->toArray($request));
        }
    }
}
