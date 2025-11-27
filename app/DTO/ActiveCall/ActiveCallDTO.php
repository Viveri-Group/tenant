<?php

namespace App\DTO\ActiveCall;

class ActiveCallDTO
{
    public function __construct(
        public int $id,
        public int $organisation_id,
        public ?int $competition_id,
        public int $call_id,
        public ?int $participant_id,
        public ?int $competition_phone_line_id,
        public string $competition_phone_number,
        public string $caller_phone_number,
        public ?string $status,
        public ?string $round_start,
        public ?string $round_end,
        public ?string $call_end,
        public int $cli_presentation,
        public ?int $audioFileNumber,
        public string $created_at,
        public string $updated_at,
    )
    {
    }
}
