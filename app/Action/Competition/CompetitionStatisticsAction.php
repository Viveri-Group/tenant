<?php

namespace App\Action\Competition;

use App\Models\Competition;
use App\Models\FailedEntry;
use App\Models\Participant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CompetitionStatisticsAction
{
    public function handle(Competition $competition): array
    {
        $roundInfo = app(GetCompetitionRoundsAction::class)->handle($competition);

        // Assuming only 1 round exists and it is at index 0
        $round = $roundInfo[0] ?? null;

        if (!$round) {
            return []; // No round found
        }

        $start = Carbon::parse(array_key_first($round));
        $end = Carbon::parse($round[$start->toDateTimeString()]);

        $now = now();

        // If the competition hasn’t started
        if ($now->lt($start)) {
            return [];
        }

        // If the competition is finished
        if ($now->gt($end)) {
            return $this->buildStats($competition, $this->getCacheKey($competition, $start, $end), now()->addDays(60));
        }

        // Competition is ongoing
        return $this->buildStats($competition, $this->getCacheKey($competition, $start, $end), now()->addMinute());
    }

    public function getCacheKey(Competition $competition, Carbon $start, Carbon $end): string
    {
        $cacheKey = app(GetCompetitionCacheKeyAction::class)->handle($competition, $start, $end);

        return 'competition_stats_' . $cacheKey;
    }

    protected function buildStats(Competition $competition, string $cacheKey, Carbon $ttl): array
    {
        return Cache::remember($cacheKey, $ttl, function () use ($competition) {
            $participantsCount = Participant::query()
                ->where('competition_id', $competition->id)
                ->whereBetween('call_start', [$competition->start, $competition->end])
                ->count();

            $latestParticipant = Participant::select(['call_start'])
                ->where('competition_id', $competition->id)
                ->whereBetween('call_start', [$competition->start, $competition->end])
                ->orderByDesc('id')
                ->first();

            $latestFailedEntry = FailedEntry::select(['call_start'])
                ->where('competition_id', $competition->id)
                ->whereBetween('call_start', [$competition->start, $competition->end])
                ->orderByDesc('id')
                ->first();

            $failedEntries = FailedEntry::query()
                ->select('reason', DB::raw('COUNT(reason) as count'))
                ->where('competition_id', $competition->id)
                ->whereBetween('call_start', [$competition->start, $competition->end])
                ->groupBy('reason')
                ->pluck('count', 'reason');

            $totalFailedEntries = $failedEntries->sum();
            $totalEntries = $participantsCount + $totalFailedEntries;

            return [
                'success' => [
                    'entries' => number_format($participantsCount),
                    'latest' => $latestParticipant?->call_start->setTimezone('Europe/London')->format('jS F Y - g:i:sa') ?? 'N/A',
                ],
                'fail' => [
                    'entries' => number_format($totalFailedEntries),
                    'reasons' => $failedEntries->toArray(),
                    'latest' => $latestFailedEntry?->call_start->setTimezone('Europe/London')->format('jS F Y - g:i:sa') ?? 'N/A',
                ],
                'total' => [
                    'entries' => number_format($totalEntries),
                    'health' => $totalEntries > 0
                        ? round(($participantsCount / $totalEntries) * 100, 2)
                        : 0
                ]
            ];
        });
    }
}
