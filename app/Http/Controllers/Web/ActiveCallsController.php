<?php

namespace App\Http\Controllers\Web;

use App\Action\GetPaginatedResourceAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\WebActiveCallsResource;
use App\Models\ActiveCall;
use App\Models\PhoneBookEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ActiveCallsController extends Controller
{
    const RESULTS_PER_PAGE = 50;

    protected array $sortableColumns = ["ID" => "id", "Created" => "created_at"];

    public function index(Request $request)
    {
        if (empty($request->all())) {
            return redirect()->route('web.active-calls.index', [
                'order_by' => 'id',
                'order_by_direction' => 'desc',
            ]);
        };

        $activeCalls = $this->filter($request)->paginate(self::RESULTS_PER_PAGE)->withQueryString();

        return Inertia::render(
            'Auth/ActiveCalls/Index',
            [
                'activeCalls' => (new GetPaginatedResourceAction())->handle(
                    $activeCalls,
                    WebActiveCallsResource::collection($activeCalls->items())
                ),
                'defaultSearchFormOptions' => [
                    'orderBy' => [
                        'availableColumns' => $this->sortableColumns,
                        'column' => 'id',
                        'direction' => 'desc'
                    ],
                    'phone_book' => collect(PhoneBookEntry::all())->map(fn($phoneBook) => ['label' => $phoneBook->phone_number, 'value' => $phoneBook->phone_number])->toArray()
                ],
                'enableMaxLines' => false,
                'maxActiveLines' => 0,
            ]);
    }


    protected function filter(Request $request): Builder
    {
        $orderByColumn = 'id';
        $orderDirection = 'desc';

        if ($request->input('order_by') && in_array($request->input('order_by'), array_values($this->sortableColumns))) {
            $orderByColumn = $request->input('order_by');
        }

        if ($request->input('order_by_direction') && in_array($request->input('order_by_direction'), ['asc', 'desc'])) {
            $orderDirection = $request->input('order_by_direction');
        }

        $activeCalls = ActiveCall::query()
            ->orderBy($orderByColumn, $orderDirection);

        if ($dateFrom = $request->input('date_from')) {
            $activeCalls->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $activeCalls->where('created_at', '<=', $dateTo);
        }

        if ($competition_id = $request->input('competition_id')) {
            $activeCalls->where('competition_id', $competition_id);
        }

        if ($call_id = $request->input('call_id')) {
            $activeCalls->whereLike('call_id', '%'.$call_id.'%');
        }

        if ($phone_number = $request->input('phone_number')) {
            $activeCalls->where('phone_number', $phone_number);
        }

        if ($caller_phone_number = $request->input('caller_phone_number')) {
            $activeCalls->whereLike('caller_phone_number', '%' . Str::replace(' ', '',$caller_phone_number) . '%');
        }

        return $activeCalls;
    }
}
