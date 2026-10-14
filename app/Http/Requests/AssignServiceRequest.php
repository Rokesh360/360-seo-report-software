<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;

class AssignServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'client_id'    => ['required', 'exists:clients,id'],
            'service_id'   => ['required', 'array', 'min:1'],
            'service_id.*' => ['integer', 'exists:services,id'],
            'from_date'    => ['required', 'date'],
            'to_date'      => ['required', 'date', 'after_or_equal:from_date'],
            'title'        => ['required', 'string', 'max:255'],
            'budget'       => ['required', 'integer', 'min:0'],
            'description'  => ['nullable', 'string'],
            'status'       => ['nullable', 'integer', 'in:0,1'],
            'platforms'          => ['nullable', 'string', 'max:255'],
            'campaign_objective' => ['nullable', 'string', 'max:255'],
            'campaign_overview'  => ['nullable', 'string'],
            'target_audience'    => ['nullable', 'string'],
            'campaign_focus'     => ['nullable', 'string'],
            'key_highlights'     => ['nullable', 'string'],
        ];

        $reportServiceSelected = Service::query()
            ->whereIn('id', (array) $this->input('service_id', []))
            ->whereIn('name', [
                'WhatsApp Meta Ads Monthly Report',
                'Lead Generation Ads Monthly Report',
                'Social Media Engagement Monthly Report',
            ])
            ->exists();

        if ($reportServiceSelected) {
            $rules += [
                'key_observations' => ['nullable', 'array'],
                'key_observations.creative_attention' => ['nullable', 'string'],
                'key_observations.audience_response' => ['nullable', 'string'],
                'key_observations.placement_interaction' => ['nullable', 'string'],
                'performance_highlights' => ['nullable', 'array'],
                'performance_highlights.notes' => ['nullable', 'string'],
                'monthly_summary' => ['nullable', 'array'],
                'monthly_summary.overall' => ['nullable', 'string'],
                'monthly_summary.enquiry' => ['nullable', 'string'],
                'monthly_summary.engagement' => ['nullable', 'string'],
                'monthly_summary.brand_visibility' => ['nullable', 'string'],
                'next_month_plan' => ['nullable', 'array'],
                'next_month_plan.test_creatives' => ['nullable', 'string'],
                'next_month_plan.audience_targeting' => ['nullable', 'string'],
                'next_month_plan.retargeting' => ['nullable', 'string'],
                'next_month_plan.messaging' => ['nullable', 'string'],
                'next_month_plan.high_performers' => ['nullable', 'string'],
                'next_month_plan.quality_leads' => ['nullable', 'string'],
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'platforms'          => 'platforms',
            'campaign_objective' => 'campaign objective',
            'campaign_overview'  => 'campaign overview',
            'target_audience'    => 'target audience',
            'campaign_focus'     => 'main campaign focus',
            'key_highlights'     => 'key highlights',
            'budget'             => 'Budget',
            'key_observations.creative_attention' => 'creative attention',
            'key_observations.audience_response' => 'audience response',
            'key_observations.placement_interaction' => 'placement interaction',
            'performance_highlights.notes' => 'performance highlights',
            'monthly_summary.overall' => 'overall performance summary',
            'monthly_summary.enquiry' => 'enquiry / lead generation summary',
            'monthly_summary.engagement' => 'audience engagement summary',
            'monthly_summary.brand_visibility' => 'brand visibility summary',
            'next_month_plan.test_creatives' => 'new creatives plan',
            'next_month_plan.audience_targeting' => 'audience targeting plan',
            'next_month_plan.retargeting' => 'retargeting plan',
            'next_month_plan.messaging' => 'messaging plan',
            'next_month_plan.high_performers' => 'high-performing audience plan',
            'next_month_plan.quality_leads' => 'quality leads plan',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required'    => 'Please select a client.',
            'service_id.required'   => 'Please select at least one service.',
            'service_id.array'      => 'Services must be selected as a list.',
            'service_id.*.exists'   => 'One of the selected services is invalid.',
            'budget.required'       => 'Budget is required.',
            'budget.integer'        => 'Budget must be an integer.',
            'budget.min'            => 'Budget must be a positive number.',
            'from_date.required'    => 'From date is required.',
            'to_date.after_or_equal' => 'To date must be same or after the from date.',
            'title.required'        => 'Title is required.',
            'status.in'             => 'Status must be either Active or Inactive.',
            'key_observations.*.required' => 'Please complete all key observation fields.',
            'performance_highlights.notes.required' => 'Please provide the performance highlights.',
            'monthly_summary.*.required' => 'Please complete all monthly summary fields.',
            'next_month_plan.*.required' => 'Please complete all next month plan fields.',
        ];
    }
}
