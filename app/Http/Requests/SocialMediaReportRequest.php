<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialMediaReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'campaign_results', 'creative_performance', 'audience_performance',
            'top_posts', 'content_analysis', 'performance_highlights',
            'key_observations', 'monthly_summary', 'next_month_plan',
        ] as $key) {
            if (is_string($this->input($key))) {
                $decoded = json_decode($this->input($key), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$key => $decoded]);
                }
            }
        }
    }

    public function rules(): array
    {
        $reportType = $this->input('report_type');

        $rules = [
            'report_type' => [
                'required',
                Rule::in([
                    'whatsapp_meta_ads',
                    'lead_generation_ads',
                    'social_media_engagement',
                    'all',
                ]),
            ],

            //  client_id replaces client_name
            'client_id' => ['required', 'integer', 'exists:clients,id'],

            'reporting_period_start' => ['required', 'date'],
            'reporting_period_end'   => ['required', 'date', 'after_or_equal:reporting_period_start'],
            'platforms'              => ['nullable', 'string', 'max:255'],
            'campaign_objective'     => ['nullable', 'string', 'max:255'],
            'campaign_overview'      => ['nullable', 'string'],
            'target_audience'        => ['nullable', 'string'],
            'campaign_focus'         => ['nullable', 'string'],
            'key_highlights'         => ['nullable', 'string'],

            'campaign_results'       => ['nullable', 'array'],
            'creative_performance'   => ['nullable', 'array'],
            'audience_performance'   => ['nullable', 'array'],
            'top_posts'              => ['nullable', 'array'],
            'content_analysis'       => ['nullable', 'array'],
            'performance_highlights' => ['nullable', 'array'],
            'key_observations'       => ['nullable', 'array'],
            'monthly_summary'        => ['nullable', 'array'],
            'next_month_plan'        => ['nullable', 'array'],
        ];

        if (in_array($reportType, ['whatsapp_meta_ads', 'all'], true)) {
            $required = $reportType === 'whatsapp_meta_ads' ? 'required' : 'nullable';
            $rules = array_merge($rules, [
                'total_conversations' => [$required, 'integer', 'min:0'],
                'total_enquiries'     => [$required, 'integer', 'min:0'],
                'whatsapp_clicks'     => [$required, 'integer', 'min:0'],
            ]);
        }

        if (in_array($reportType, ['lead_generation_ads', 'all'], true)) {
            $required = $reportType === 'lead_generation_ads' ? 'required' : 'nullable';
            $rules = array_merge($rules, [
                'total_leads'      => [$required, 'integer', 'min:0'],
                'form_submissions' => [$required, 'integer', 'min:0'],
                'link_clicks'      => [$required, 'integer', 'min:0'],
            ]);
        }

        if (in_array($reportType, ['social_media_engagement', 'all'], true)) {
            $required = $reportType === 'social_media_engagement' ? 'required' : 'nullable';
            $rules = array_merge($rules, [
                'total_engagement' => [$required, 'integer', 'min:0'],
                'reactions'        => [$required, 'integer', 'min:0'],
                'comments'         => [$required, 'integer', 'min:0'],
                'shares'           => [$required, 'integer', 'min:0'],
                'saves'            => [$required, 'integer', 'min:0'],
                'profile_visits'   => [$required, 'integer', 'min:0'],
                'new_followers'    => [$required, 'integer', 'min:0'],
            ]);
        }

        if (in_array($reportType, ['whatsapp_meta_ads', 'lead_generation_ads', 'social_media_engagement'], true)) {
            $rules['reach']       = ['required', 'integer', 'min:0'];
            $rules['impressions'] = ['required', 'integer', 'min:0'];
            $rules['frequency']   = ['required', 'numeric', 'min:0'];
        } else {
            $rules['reach']       = ['nullable', 'integer', 'min:0'];
            $rules['impressions'] = ['nullable', 'integer', 'min:0'];
            $rules['frequency']   = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }
}