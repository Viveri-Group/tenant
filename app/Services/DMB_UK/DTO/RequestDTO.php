<?php

namespace App\Services\DMB_UK\DTO;

use Illuminate\Contracts\Support\Arrayable;

abstract class RequestDTO implements Arrayable
{
    protected array $hidden = [];

    public function toArrayWithoutHiddenFields(): array
    {
        $elements = $this->toArray();

        collect($this->hidden)->each(function ($value, $key) use (&$elements) {
            if (array_key_exists($key, $elements)) {
                $elements[$key] = $value;
            }
        });

        return $elements;
    }
}
