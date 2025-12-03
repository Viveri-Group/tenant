<?php

namespace App\Enums;

enum SMSType: string
{
    case SMS_OFFER_ACCEPTED = 'SMS_OFFER_ACCEPTED';

    case FIRST_COMPETITION_ENTRY = 'FIRST_COMPETITION_ENTRY';
}
