<?php

namespace App\Http\Controllers\Web;

use App\Action\GetPaginatedResourceAction;
use App\Enums\FailedEntryReason;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\WebFailedEntryResource;
use App\Models\FailedEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FailedEntriesController extends Controller
{
    const RESULTS_PER_PAGE = 50;

    public function index(Request $request)
    {
        if (empty($request->all())) {
            return redirect()->route('web.entries.failed.index', [
                'date_from' => now('UTC')->startOfDay()->format('Y-m-d\TH:i:s'),
                'date_to' => now('UTC')->endOfDay()->format('Y-m-d\TH:i:s'),
            ]);
        };

        $failedEntries = $this->filter($request)->paginate(self::RESULTS_PER_PAGE)->withQueryString();

        return Inertia::render(
            'Auth/FailedEntries/Index',
            [
                'failedEntries' => (new GetPaginatedResourceAction())->handle(
                    $failedEntries,
                    WebFailedEntryResource::collection($failedEntries->items())
                ),
                'defaultSearchFormOptions' => [
                    'date_from' => now('UTC')->startOfDay()->format('Y-m-d\TH:i:s'),
                    'date_to' => now('UTC')->endOfDay()->format('Y-m-d\TH:i:s'),
                    'reasons' => collect(FailedEntryReason::options())->map(fn($reason) => ['label' => $reason, 'value' => $reason])->sortBy('label')->values()->toArray()
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

        $failedEntries = FailedEntry::query()
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->orderByDesc('id');

        if ($competition_id = $request->input('competition_id')) {
            $failedEntries->where('competition_id', $competition_id);
        }

        if ($call_id = $request->input('call_id')) {
            $failedEntries->where('call_id', $call_id);
        }

        if ($phone_number = $request->input('phone_number')) {
            $failedEntries->whereLike('phone_number', '%' . Str::replace(' ', '',$phone_number) . '%');
        }

        if ($caller_phone_number = $request->input('caller_phone_number')) {
            $failedEntries->whereLike('caller_phone_number', '%' . Str::replace(' ', '',$caller_phone_number) . '%');
        }

        if ($request->input('organisation_id')) {
            $failedEntries->where('organisation_id', $request->input('organisation_id'));
        }

//        if ($reason = $request->input('reason')) {
//            if($request->input('reason') === FailedEntryReason::DTMF_INVALID->name){
//                $failedEntries->whereLike('reason', '%' . $reason . '%');
//            }else{
//                $failedEntries->where('reason', $reason);
//            }
//        }

        return $failedEntries;
    }

}
