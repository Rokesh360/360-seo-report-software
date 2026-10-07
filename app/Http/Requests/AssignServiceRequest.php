<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'    => ['required', 'exists:clients,id'],
            'service_id'   => ['required', 'array', 'min:1'],
            'service_id.*' => ['integer', 'exists:services,id'],
            'from_date'    => ['required', 'date'],
            'to_date'      => ['required', 'date', 'after_or_equal:from_date'],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'status'       => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required'    => 'Please select a client.',
            'service_id.required'   => 'Please select at least one service.',
            'service_id.array'      => 'Services must be selected as a list.',
            'service_id.*.exists'   => 'One of the selected services is invalid.',
            'from_date.required'    => 'From date is required.',
            'to_date.after_or_equal' => 'To date must be same or after the from date.',
            'title.required'        => 'Title is required.',
            'status.in'             => 'Status must be either Active or Inactive.',
        ];
    }
}
