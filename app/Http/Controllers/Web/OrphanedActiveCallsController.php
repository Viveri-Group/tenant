<?php

namespace App\Http\Controllers\Web;

use App\Action\GetPaginatedResourceAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\WebOrphanActiveCallsResource;
use App\Models\ActiveCallOrphan;
use App\Models\Participant;
use App\Models\PhoneBookEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OrphanedActiveCallsController extends Controller
{
    const RESULTS_PER_PAGE = 50;

    public function index(Request $request)
    {
        if (empty($request->all())) {
            return redirect()->route('web.orphan-active-calls.index', [
                'date_from' => now('UTC')->startOfDay()->format('Y-m-d\TH:i:s'),
                'date_to' => now('UTC')->endOfDay()->format('Y-m-d\TH:i:s'),
            ]);
        };

        $orphanActiveCalls = $this->filter($request)->paginate(self::RESULTS_PER_PAGE)->withQueryString();

        return Inertia::render(
            'Auth/OrphanedActiveCalls/Index',
            [
                'orphanActiveCalls' => (new GetPaginatedResourceAction())->handle(
                    $orphanActiveCalls,
                    WebOrphanActiveCallsResource::collection($orphanActiveCalls->items())
                ),
                'defaultSearchFormOptions' => [
                    'date_from' => now('UTC')->startOfDay()->format('Y-m-d\TH:i:s'),
                    'date_to' => now('UTC')->endOfDay()->format('Y-m-d\TH:i:s'),
                    'phone_book' => collect(PhoneBookEntry::all())->map(fn($phoneBook) => ['label' => $phoneBook->phone_number, 'value' => $phoneBook->phone_number])->toArray()
                ]
            ]);
    }


    protected function filter(Request $request): Builder
    {
        $dateFrom = Carbon::now('UTC')->subDays(30);
        $dateTo = Carbon::now('UTC');

        if ($request->input('date_from')) {
            $dateFrom = Carbon::parse($request->input('date_from'), 'Europe/London')->setTimezone('UTC');
        }

        if ($request->input('date_to')) {
            $dateTo = Carbon::parse($request->input('date_to'), 'Europe/London')->setTimezone('UTC');
        }

        $orphanActiveCalls = ActiveCallOrphan::query()
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->orderByDesc('id');

        if ($competition_id = $request->input('competition_id')) {
            $orphanActiveCalls->where('competition_id', $competition_id);
        }

        if ($call_id = $request->input('call_id')) {
            $orphanActiveCalls->whereLike('call_id', '%'.$call_id.'%');
        }

        if ($phone_number = $request->input('phone_number')) {
            $orphanActiveCalls->where('phone_number', $phone_number);
        }

        if ($caller_phone_number = $request->input('caller_phone_number')) {
            $orphanActiveCalls->whereLike('caller_phone_number', '%' . Str::replace(' ', '',$caller_phone_number) . '%');
        }

        if ($request->input('organisation_id')) {
            $orphanActiveCalls->where('organisation_id', $request->input('organisation_id'));
        }

        return $orphanActiveCalls;
    }
}
