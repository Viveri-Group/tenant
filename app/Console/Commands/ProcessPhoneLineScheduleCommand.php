<?php

namespace App\Console\Commands;

use App\Action\PhoneBook\GetPhoneBookEntriesKeyedAction;
use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\PhoneLineSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProcessPhoneLineScheduleCommand extends Command
{
    protected $signature = 'viveri:process-phone-line-schedule';

    protected $description = 'Cron task: reassign competition phone line via scheduler.';

    public function handle(): void
    {
        $keyedPhoneBookEntries = (new GetPhoneBookEntriesKeyedAction())->handle();

        PhoneLineSchedule::query()
            ->where('action_at', '<=', now())
            ->where('processed', false)
            ->orderBy('action_at', 'asc')
            ->chunk(100, function (Collection $schedules) use ($keyedPhoneBookEntries) {
                foreach ($schedules as $schedule) {
                    $competition = Competition::find($schedule->competition_id);

                    if (!$competition) {
                        $schedule->update([
                            'processed' => true,
                            'completed_at' => now(),
                            'notes' => "Schedule {$schedule->id} skipped: competition {$schedule->competition_id} does not exist.",
                            'success' => false,
                        ]);

                        continue;
                    }


                    DB::transaction(function () use ($schedule, $competition, $keyedPhoneBookEntries) {
                        CompetitionPhoneLine::query()
                            ->where('phone_number', $schedule->competition_phone_number)
                            ->delete();

                        $competition->phoneLines()->create([
                            'phone_number' => $schedule->competition_phone_number,
                        ]);

                        $schedule->update([
                            'processed' => true,
                            'completed_at' => now(),
                            'notes' => "Successfully moved phone number: {$schedule->competition_phone_number} to competition {$schedule->competition_id}.",
                            'success' => true,
                        ]);
                    });
                }
            });
    }
}
