<?php

namespace App\Jobs;

use App\Action\Helpers\IsUKMobileAction;
use App\Enums\SMSType;
use App\Models\Competition;
use App\Services\DMB_UK\Requests\SMS\SendSMS;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SMSFirstEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $competitionId, public string $callerNumber)
    {
    }

    public function handle(): void
    {
        $competition = Competition::findOrFail($this->competitionId);

        if($competition->sms_first_entry_enabled && $competition->sms_first_entry_message && (new IsUKMobileAction())->handle($this->callerNumber)){
            (new SendSMS(
                $this->callerNumber,
                $competition->sms_first_entry_message,
                SMSType::FIRST_COMPETITION_ENTRY->name,
                $competition->sms_mask,
            ))->handle();

            SMSWhiteListCallerJob::dispatch(
                $this->callerNumber,
                $competition->promo_code_id_first_entry
            );
        }
    }
}
