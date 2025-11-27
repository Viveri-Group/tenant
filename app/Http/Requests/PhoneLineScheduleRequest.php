<?php

namespace App\Http\Requests;

use App\Action\PhoneBook\PhoneBookLookupAction;
use App\Models\PhoneLineSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PhoneLineScheduleRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        if ($this->competition_phone_number) {

            $entry = (new PhoneBookLookupAction())
                ->handle($this->competition_phone_number);

            $this->merge([
                'organisation_id' => $entry?->organisation_id,
            ]);
        }
    }


    public function rules(): array
    {
        $scheduleParam = $this->route('phoneLineSchedule');
        $scheduleId = $scheduleParam instanceof PhoneLineSchedule ? $scheduleParam->id : $scheduleParam;

        return [
            'organisation_id' => ['required', 'exists:organisations,id'],

            'competition_id' => ['integer', 'required', 'exists:competitions,id'],

            'competition_phone_number' => [
                'required',
                'string',

                Rule::exists('phone_book_entries', 'phone_number'),

                Rule::unique('phone_line_schedules', 'competition_phone_number')
                    ->ignore($scheduleId, 'id')
                    ->where(fn($query) => $query->where('action_at', $this->action_at)),
            ],

            'action_at' => ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/', 'after:now'], // 2023-12-05T14:30:59Z

            'author_id' => ['sometimes', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'competition_phone_number.unique' => 'A schedule for this phone number already exists at the specified action time.',
        ];
    }
}
