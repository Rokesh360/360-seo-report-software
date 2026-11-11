<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GstMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('gstMaster')?->id;

        return [
            'name'       => ['required', 'string', 'max:100', Rule::unique('gst_masters', 'name')->ignore($id)],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'status'     => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'    => 'This GST name already exists.',
            'percentage.max' => 'Percentage cannot be more than 100.',
        ];
    }
}