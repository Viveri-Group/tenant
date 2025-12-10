<?php

namespace App\Http\Controllers\Web;

use App\Action\Competition\CompetitionStatisticsAction;
use App\Action\GetPaginatedResourceAction;
use App\Action\PhoneBook\GetPhoneBookEntriesAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\WebCompetitionResource;
use App\Http\Resources\Web\WebPhoneBookEntryResource;
use App\Models\Competition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class CompetitionController extends Controller
{
    const RESULTS_PER_PAGE = 50;

    public function index(Request $request)
    {
        if (empty($request->all())) {
            return redirect()->route('web.competition.index', [
                'date_from' => now('UTC')->subWeeks(2)->startOfDay()->format('Y-m-d\TH:i:s'),
                'date_to' => now('UTC')->addWeeks(2)->endOfDay()->format('Y-m-d\TH:i:s'),
            ]);
        };

        $competitions = $this->filter($request)->paginate(self::RESULTS_PER_PAGE)->withQueryString();

        return Inertia::render(
            'Auth/Competitions/Index',
            [
                'competitions' => (new GetPaginatedResourceAction())->handle(
                    $competitions,
                    WebCompetitionResource::collection($competitions->items())
                ),
                'defaultSearchFormOptions' => [
                    'date_from' => now('UTC')->subWeeks(2)->startOfDay()->format('Y-m-d\TH:i:s'),
                    'date_to' => now('UTC')->addWeeks(2)->endOfDay()->format('Y-m-d\TH:i:s'),
                ]
            ]);
    }

    public function show(Request $request, Competition $competition)
    {
        return Inertia::render(
            'Auth/Competitions/Show', [
                'competition' => new WebCompetitionResource($competition),
                'statistics' => (new CompetitionStatisticsAction())->handle($competition),
                'phoneBookEntries' => WebPhoneBookEntryResource::collection((new GetPhoneBookEntriesAction())->handle()),
            ]
        );
    }

    protected function filter(Request $request): Builder
    {
        $dateFrom = Carbon::now('UTC')->subWeeks(2)->startOfDay();
        $dateTo = Carbon::now('UTC')->addWeeks(2)->endOfDay();

        if ($request->input('date_from')) {
            $dateFrom = Carbon::parse($request->input('date_from'), 'Europe/London')->setTimezone('UTC');
        }

        if ($request->input('date_to')) {
            $dateTo = Carbon::parse($request->input('date_to'), 'Europe/London')->setTimezone('UTC');
        }

        $competitions = Competition::query()
            ->where('start', '>=', $dateFrom)
            ->where('start', '<=', $dateTo)
            ->orderByDesc('id');

        if ($name = $request->input('name')) {
            $competitions->whereLike('name', '%' . $name . '%');
        }

        if ($request->input('organisation_id')) {
            $competitions->where('organisation_id', $request->input('organisation_id'));
        }

        return $competitions;
    }
}
