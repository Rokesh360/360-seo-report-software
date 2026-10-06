<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assignServiceId = $this->route('assign-services')?->id;

        return [
            'client_id' => [
                'required',
                'exists:clients,id',
            ],
            'service_id' => [
                'required',
                'exists:services,id',
                // Prevent duplicate client+service pair (optional but useful)
                Rule::unique('assign_services')
                    ->where(fn ($q) => $q->where('client_id', $this->client_id)
                                        ->whereNull('deleted_at'))
                    ->ignore($assignServiceId),
            ],
            'from_date' => ['required', 'date'],
            'to_date'   => ['required', 'date', 'after_or_equal:from_date'],
            'title'     => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required'  => 'Please select a client.',
            'service_id.required' => 'Please select a service.',
            'service_id.unique'   => 'This service is already assigned to the selected client.',
            'from_date.required'  => 'From date is required.',
            'to_date.after_or_equal' => 'To date must be same or after the from date.',
            'title.required'      => 'Title is required.',
            'status.in'            => 'Status must be either Active or Inactive.',
        ];
    }
}