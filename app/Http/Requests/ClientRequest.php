<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:255',
                          Rule::unique('clients', 'name')->ignore($this->route('client'))],
            'website' => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255'],
            'phone'   => ['required', 'string', 'max:30'],
            'date_of_campaign' => ['required', 'date'],
            'status'  => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Please enter the client name.',
            'name.unique'      => 'A client with this name already exists.',
            'website.required' => 'Please enter the client website.',
            'website.max'      => 'Website must not exceed 255 characters.',
            'email.email'      => 'Please enter a valid email address.',
            'phone.max'        => 'Phone must not exceed 30 characters.',
            'date_of_campaign.required' => 'Please enter the date of campaign.',
            'date_of_campaign.date'     => 'Please enter a valid date for the campaign.',
            'status.required'  => 'Please choose a status.',
            'status.boolean'   => 'Status must be Active or Inactive.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'    => 'client name',
            'website' => 'website',
            'email'   => 'email',
            'phone'   => 'phone',
            'status'  => 'status',
        ];
    }
}