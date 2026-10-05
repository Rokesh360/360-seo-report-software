<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeoReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'        => 'required|exists:clients,id',
            'reporting_period' => 'required|string|max:255',
            'prepared_by'      => 'required|string|max:255',
            'overview'         => 'nullable|string',

            'on_page_items'    => 'nullable|array',
            'on_page_items.*'  => 'string|max:255',

            'backlinks_items'   => 'nullable|array',
            'backlinks_items.*' => 'string|max:255',

            'seo_score_image'  => [$this->isMethod('post') ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            'performance_images'            => 'nullable|array',
            'performance_images.*.title'    => 'nullable|string|max:255',
            'performance_images.*.existing' => 'nullable|string|max:500',
            'performance_images.*.file'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',

            'keywords'                 => 'required|array|min:1',
            'keywords.*.keyword'       => 'required|string|max:255',
            'keywords.*.page'          => 'nullable|integer|between:1,10',
            'keywords.*.position'      => 'nullable|integer|between:1,10',
            'keywords.*.image_title'   => 'nullable|string|max:255',
            'keywords.*.image_existing' => 'nullable|string|max:500',
            'keywords.*.image_file'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',

            'images'            => 'nullable|array',
            'images.*.title'    => 'nullable|string|max:255',
            'images.*.existing' => 'nullable|string|max:500',
            'images.*.file'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096|required_without:images.*.existing',

            'conclusion' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required'       => 'Please select a client.',
            'client_id.exists'         => 'Selected client is invalid.',
            'seo_score_image.required' => 'Please upload the SEO score image.',
            'seo_score_image.image'    => 'SEO score file must be an image.',
            'keywords.*.keyword.required' => 'Keyword row :position is required.',
            'keywords.*.image_file.image' => 'Keyword screenshot row :position must be an image.',
            'keywords.*.image_file.max'   => 'Keyword screenshot row :position must not exceed 4 MB.',
            'images.*.file.required_without' => 'Image row :position needs an image file.',
            'images.*.file.image'         => 'Image row :position must be an image.',
            'images.*.file.max'           => 'Image row :position must not exceed 4 MB.',
            'performance_images.*.file.image' => 'Performance image row :position must be an image.',
            'performance_images.*.file.max'   => 'Performance image row :position must not exceed 4 MB.',
        ];
    }
}
