<?php

namespace App\Console\Commands;

use App\Models\ActiveCall;
use App\Models\ActiveCallOrphan;
use Illuminate\Console\Command;

class ActiveCallClearUpCommand extends Command
{
    protected $signature = 'viveri:active-call-clear-up';

    protected $description = 'Clears away orphaned active call entries that are older than 2 minutes.';

    public function handle(): void
    {
        ActiveCall::where('created_at', '<=', now()->subMinutes(2))
            ->each(function (ActiveCall $call) {
                ActiveCallOrphan::create([
                    'organisation_id' => $call->organisation_id,
                    'competition_id' => $call->competition_id,
                    'call_id' => $call->call_id,
                    'phone_number' => $call->phone_number,
                    'caller_phone_number' => $call->caller_phone_number,
                    'status' => $call->status,
                    'original_call_time' => $call->created_at,
                ]);

                $call->delete();
            });
    }
}
