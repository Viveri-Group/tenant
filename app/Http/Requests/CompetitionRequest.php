<?php

namespace App\Http\Requests;

use App\Enums\CompetitionSpecialOffer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CompetitionRequest extends FormRequest
{
    public function rules(): array
    {
        $competition = $this->route('competition');

        if ($competition) {
            return [
                'name' => ['required', 'string'],
                'start' => ['required', 'date', 'before:end'],
                'end' => ['required', 'date', 'after:start'],
                'special_offer' => ['nullable', 'string', new Enum(CompetitionSpecialOffer::class)],
                'max_entries' => ['required', 'integer'],

                'sms_mask' => ['nullable', 'string', 'max:11'],
                'sms_offer_enabled' => ['nullable', 'boolean'],
                'sms_offer_message' => ['nullable', 'string', 'max:160'],
                'sms_first_entry_enabled' => ['nullable', 'boolean'],
                'sms_first_entry_message' => ['nullable', 'string', 'max:160'],
            ];
        }

        return [
            'organisation_id' => ['required', 'integer'],
            'name' => ['required', 'string'],
            'start' => ['required', 'date', 'before:end'],
            'end' => ['required', 'date', 'after:start'],
            'special_offer' => ['nullable', 'string', new Enum(CompetitionSpecialOffer::class)],
            'max_entries' => ['required', 'integer'],

            'sms_mask' => ['nullable', 'string', 'max:11'],
            'sms_offer_enabled' => ['nullable', 'boolean'],
            'sms_offer_message' => ['nullable', 'string', 'max:160'],
            'sms_first_entry_enabled' => ['nullable', 'boolean'],
            'sms_first_entry_message' => ['nullable', 'string', 'max:160'],
        ];

    }

    public function authorize(): bool
    {
        return true;
    }
}
