<?php

namespace App\Http\Controllers\Web;

use App\Action\GetPaginatedResourceAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\WebParticipantResource;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ParticipantsController extends Controller
{
    const RESULTS_PER_PAGE = 50;

    public function index(Request $request)
    {
        if (empty($request->all())) {
            return redirect()->route('web.participants.index', [
                'date_from' => now('UTC')->startOfDay()->format('Y-m-d\TH:i:s'),
                'date_to' => now('UTC')->endOfDay()->format('Y-m-d\TH:i:s'),
            ]);
        };

        $participants = $this->filter($request)->paginate(self::RESULTS_PER_PAGE)->withQueryString();

        return Inertia::render(
            'Auth/Participants/Index',
            [
                'participants' => (new GetPaginatedResourceAction())->handle(
                    $participants,
                    WebParticipantResource::collection($participants->items())
                ),
                'defaultSearchFormOptions' => [
                    'date_from' => now('UTC')->startOfDay()->format('Y-m-d\TH:i:s'),
                    'date_to' => now('UTC')->endOfDay()->format('Y-m-d\TH:i:s'),
                ]
            ]);
    }

    protected function filter(Request $request): Builder
    {
        $dateFrom = Carbon::now('UTC')->subDays(30)->format('Y-m-d\TH:i:s');
        $dateTo = Carbon::now('UTC')->format('Y-m-d\TH:i:s');

        if ($request->input('date_from')) {
            $dateFrom = Carbon::parse($request->input('date_from'), 'Europe/London')->setTimezone('UTC');
        }

        if ($request->input('date_to')) {
            $dateTo = Carbon::parse($request->input('date_to'), 'Europe/London')->setTimezone('UTC');
        }

        $participants = Participant::query()
            ->where('call_start', '>=', $dateFrom)
            ->where('call_start', '<=', $dateTo)
            ->orderByDesc('id');

        if ($competition_id = $request->input('competition_id')) {
            $participants->where('competition_id', $competition_id);
        }

        if ($call_id = $request->input('call_id')) {
            $participants->where('call_id', $call_id);
        }

//        if ($competition_phone_line_id = $request->input('competition_phone_line_id')) {
//            $participants->where('competition_phone_line_id', $competition_phone_line_id);
//        }

        if ($telephone = $request->input('telephone')) {
            $participants->whereLike('telephone', '%' . Str::replace(' ', '', $telephone) . '%');
        }

        if ($call_start = $request->input('call_start')) {
            $participants->where('call_start', $call_start);
        }

        if ($request->input('organisation_id')) {
            $participants->where('organisation_id', $request->input('organisation_id'));
        }

        return $participants;
    }
}
