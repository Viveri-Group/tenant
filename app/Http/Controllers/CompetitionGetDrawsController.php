<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompetitionDrawsCollection;
use App\Models\Competition;
use Illuminate\Http\Request;

class CompetitionGetDrawsController extends Controller
{
    public function __invoke(Request $request, Competition $competition)
    {
        return CompetitionDrawsCollection::collection($competition->draws);
    }
}
