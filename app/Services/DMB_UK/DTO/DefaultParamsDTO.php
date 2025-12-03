<?php

namespace App\Services\DMB_UK\DTO;

use App\Services\DMB_UK\DTO\RequestDTO;

class DefaultParamsDTO extends RequestDTO
{
    protected array $hidden = [
        'Password' => '********',
        'Username' => '********',
    ];

    public function __construct(protected array $params)
    {
    }

    public function toArray(): array
    {
        return $this->params;
    }
}
