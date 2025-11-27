<?php

namespace App\Jobs;

use App\Action\Competition\CreateParticipantFromActiveCallDTOAction;
use App\Action\Competition\LogEntrantRoundCountAction;
use App\DTO\ActiveCall\ActiveCallDTO;
use App\Enums\CompetitionStatusEnum;
use App\Models\ActiveCall;
use App\Models\CompetitionPhoneLine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ActiveCallDTO $activeCallDTO)
    {
    }

    public function handle(): void
    {
        ActiveCall::find($this->activeCallDTO->id)->update([
            'status' => CompetitionStatusEnum::COMP_OPEN_ANSWERED->value
        ]);

        $participant = (new CreateParticipantFromActiveCallDTOAction())->handle($this->activeCallDTO);

        (new LogEntrantRoundCountAction())->handle(
            $this->activeCallDTO,
            $participant
        );
    }
}
