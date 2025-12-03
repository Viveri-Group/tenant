<?php

namespace App\Services\DMB_UK\Requests\SMS;

use App\Services\DMB_UK\Requests\BaseRequest;
use Illuminate\Support\Str;

class SendSMS extends BaseRequest
{
    public function __construct(
        public string $callerNumber,
        public string $message,
        public string $smsType,
        public ?string $smsMask,
    )
    {
    }

    public function getParams(): array
    {
        return [
            'Channel' => config('services.dmb-uk.sms.channel'),
            'Username' => config('services.dmb-uk.sms.username'),
            'Password' => config('services.dmb-uk.sms.password'),
            'Shortcode' => config('services.dmb-uk.sms.shortcode'),
            'Premium' => config('services.dmb-uk.sms.premium'),
            'Mask' => $this->smsMask ?? config('services.dmb-uk.sms.mask'),
            'MSISDN' => $this->callerNumber,
            'Content' => Str::substr($this->message, 0, 160),
        ];
    }

    protected function getHttpMethod(): string
    {
        return 'get';
    }

    protected function getUri(): string
    {
        return '/Bauer_relay';
    }
}
