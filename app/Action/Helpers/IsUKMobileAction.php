<?php

namespace App\Action\Helpers;

use Illuminate\Support\Str;

class IsUKMobileAction
{
    public function handle(string $number): bool
    {
        $cleaned = Str::of($number)
            ->replaceMatches('/[\s\-\(\)\+]+/', '') // remove spaces, dashes, parentheses and +
            ->value();

        return (
                (Str::startsWith($cleaned, '447') && Str::length($cleaned) === 12) ||
                (Str::startsWith($cleaned, '07') && Str::length($cleaned) === 11)
            ) && ctype_digit($cleaned);
    }
}
