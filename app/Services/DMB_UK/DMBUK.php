<?php

namespace App\Services\DMB_UK;

class DMBUK
{
    public function __construct(
    )
    {
    }

    public function getBaseUrl(): string
    {
        return 'https://relay.dmb-uk.com/';
    }


    public function getUsername(): string
    {
        return config('services.dmb-uk.sms.username');
    }

    public function getPassword(): string
    {
        return config('services.dmb-uk.sms.password');
    }
}
