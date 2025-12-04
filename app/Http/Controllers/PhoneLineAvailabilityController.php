<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhoneLineAvailabilityRequest;
use App\Models\CompetitionPhoneLine;
use App\Models\PhoneBookEntry;

class PhoneLineAvailabilityController extends Controller
{
    public function __invoke(PhoneLineAvailabilityRequest $request)
    {
        $usedPhoneNumbers = CompetitionPhoneLine::pluck('phone_number')->toArray();

        $availablePhoneBookEntries = PhoneBookEntry::whereNotIn('phone_number', $usedPhoneNumbers)->get();

        return response()->json([
            'available_phone_numbers' => $availablePhoneBookEntries->pluck('phone_number'),
        ]);
    }
}
