<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $service = $this->route('service');
        $serviceId = $service instanceof \App\Models\Service ? $service->id : $service;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'name')
                    ->ignore($serviceId)
                    ->whereNull('deleted_at'),
            ],
            'status' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'The service name is required.',
            'name.unique'     => 'A service with this name already exists.',
            'status.required' => 'Please choose a status.',
            'status.boolean'  => 'Status must be either Active or Inactive.',
        ];
    }
}
