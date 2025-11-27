<?php

namespace App\Http\Controllers;

use App\Action\PhoneBook\PhoneBookLookupAction;
use App\Action\PhoneLine\PhoneNumberCleanupAction;
use App\Http\Requests\PhoneLineRequest;
use App\Http\Resources\PhoneLineResource;
use App\Models\Competition;
use App\Models\CompetitionPhoneLine;

class PhoneLineController extends Controller
{
    public function store(PhoneLineRequest $request, Competition $competition)
    {
        $phoneNumber = (new PhoneNumberCleanupAction())->handle($request->input('phone_number'));

        $phoneBookEntry = (new PhoneBookLookupAction())->handle($phoneNumber);

        abort_if(!$phoneBookEntry, 404, "Phone number {$phoneNumber} not found in phone book.");

        abort_if($competition->organisation_id <> $phoneBookEntry->organisation_id, 409, 'Competition and phone line - organisation mismatch.');

        $newPhoneLine = $competition->phoneLines()->create([
            'organisation_id' => $competition->organisation_id,
            'phone_number' => $request->input('phone_number'),
        ]);

        return new PhoneLineResource($newPhoneLine);
    }

    public function show(Competition $competition, CompetitionPhoneLine $phoneLine)
    {
        return new PhoneLineResource($phoneLine);
    }

    public function update(PhoneLineRequest $request, Competition $competition, CompetitionPhoneLine $phoneLine)
    {
        $phoneLine->update($request->validated());

        return new PhoneLineResource($phoneLine);
    }

    public function destroy(Competition $competition, CompetitionPhoneLine $phoneLine)
    {
        $phoneLine->delete();

        return response()->noContent();
    }
}
