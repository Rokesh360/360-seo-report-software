<?php

namespace App\Http\Requests;

use App\Models\AssignService;
use App\Models\Service;
use App\Models\SocialMediaReport;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SocialMediaReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $assignmentId = $this->input('assign_service_id');
        if (is_scalar($assignmentId) && filter_var($assignmentId, FILTER_VALIDATE_INT) !== false) {
            $this->merge(['assign_service_id' => (int) $assignmentId]);
        }
    }

    public function attributes(): array
    {
        return [
            'assign_service_id' => 'assign service',

            // WhatsApp
            'total_conversations'  => 'total WhatsApp conversations',
            'whatsapp_clicks'      => 'WhatsApp clicks',
            'whatsapp_ctr'         => 'WhatsApp CTR',
            'reach'                => 'WhatsApp reach',
            'impressions'          => 'WhatsApp impressions',
            'frequency'            => 'WhatsApp frequency',

            // Lead generation
            'total_leads'          => 'total leads',
            'link_clicks'          => 'link clicks',
            'lead_reach'           => 'lead generation reach',
            'lead_impressions'     => 'lead generation impressions',
            'lead_frequency'       => 'lead generation frequency',
            'lead_ctr'             => 'lead generation CTR',

            // Engagement
            'total_engagement'     => 'total engagement',
            'reactions'            => 'reactions',
            'comments'             => 'comments',
            'shares'               => 'shares',
            'saves'                => 'saves',
            'profile_visits'       => 'profile visits',
            'new_followers'        => 'new followers',

            'campaign_results.*.name'   => 'campaign / ad set name',
            'campaign_results.*.result' => 'campaign result',
            'campaign_results.*.spend'  => 'campaign spend',
            'campaign_results.*.note'   => 'campaign note',
            'top_posts.*.title'       => 'post title',
            'top_posts.*.engagements' => 'post engagements',
            'top_posts.*.type'        => 'post type',
        ];
    }

    public function messages(): array
    {
        return [
            'assign_service_id.required' => 'Select an assign service.',
            'campaign_results.*.name.required' => 'Row :position: campaign / ad set name is required.',
            'campaign_results.*.result.required' => 'Row :position: result is required.',
            'campaign_results.*.spend.required' => 'Row :position: spend is required.',
            'campaign_results.*.note.required' => 'Row :position: note is required.',
            'top_posts.*.title.required' => 'Row :position: post title is required.',
            'top_posts.*.engagements.required' => 'Row :position: engagements are required.',
            'top_posts.*.type.required' => 'Row :position: post type is required.',
        ];
    }

    public function rules(): array
    {
        $rules = [
            'assign_service_id' => ['required', 'integer', 'exists:assign_services,id'],
        ];

        $types = $this->selectedReportTypes();

        if (in_array('whatsapp_meta_ads', $types, true)) {
            $rules += [
                'total_conversations' => ['required', 'integer', 'min:0'],
                'whatsapp_clicks'     => ['required', 'integer', 'min:0'],
                'whatsapp_ctr'        => ['required', 'numeric', 'min:0', 'max:100'],
                'reach'               => ['required', 'integer', 'min:0'],
                'impressions'         => ['required', 'integer', 'min:0'],
                'frequency'           => ['required', 'numeric', 'min:0'],
            ];
        }

        if (in_array('lead_generation_ads', $types, true)) {
            $rules += [
                'total_leads'      => ['required', 'integer', 'min:0'],
                'link_clicks'      => ['required', 'integer', 'min:0'],
                'lead_ctr'         => ['required', 'numeric', 'min:0', 'max:100'],
                'lead_reach'       => ['required', 'integer', 'min:0'],
                'lead_impressions' => ['required', 'integer', 'min:0'],
                'lead_frequency'   => ['required', 'numeric', 'min:0'],
            ];
        }

        if (array_intersect(['whatsapp_meta_ads', 'lead_generation_ads'], $types) !== []) {
            $rules += [
                'campaign_results'          => ['required', 'array', 'min:1'],
                'campaign_results.*.name'   => ['required', 'string'],
                'campaign_results.*.result' => ['required', 'string'],
                'campaign_results.*.spend' => ['required', 'integer', 'min:0'],
                'campaign_results.*.note'   => ['required', 'string'],
            ];
        }

        if (in_array('social_media_engagement', $types, true)) {
            $rules += [
                'total_engagement' => ['required', 'integer', 'min:0'],
                'reactions'        => ['required', 'integer', 'min:0'],
                'comments'         => ['required', 'integer', 'min:0'],
                'shares'           => ['required', 'integer', 'min:0'],
                'saves'            => ['required', 'integer', 'min:0'],
                'profile_visits'   => ['required', 'integer', 'min:0'],
                'new_followers'    => ['required', 'integer', 'min:0'],
                'top_posts'               => ['required', 'array', 'min:1'],
                'top_posts.*.title'       => ['required', 'string'],
                'top_posts.*.engagements' => ['required', 'string'],
                'top_posts.*.type'        => ['required', 'string'],
            ];
        }

        return $rules;
    }

    private function selectedReportTypes(): array
    {
        $rawAssignmentId = $this->input('assign_service_id');
        if (! is_scalar($rawAssignmentId)) {
            return [];
        }

        $assignmentId = filter_var($rawAssignmentId, FILTER_VALIDATE_INT);
        if ($assignmentId === false) {
            return [];
        }

        $serviceIds = AssignService::query()->find((int) $assignmentId)?->service_id;
        $serviceIds = collect(is_array($serviceIds) ? $serviceIds : [(int) $serviceIds])
            ->filter()
            ->unique()
            ->values();

        $serviceNameToType = [
            'WhatsApp Meta Ads Monthly Report' => 'whatsapp_meta_ads',
            'Lead Generation Ads Monthly Report' => 'lead_generation_ads',
            'Social Media Engagement Monthly Report' => 'social_media_engagement',
        ];

        return Service::query()
            ->whereIn('id', $serviceIds)
            ->pluck('name')
            ->map(fn (string $name) => $serviceNameToType[$name] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $assign = AssignService::find((int) $this->input('assign_service_id'));
            $budget = (int) ($assign?->budget ?? 0);

            if ($budget <= 0) {
                return;
            }

            $spent = collect((array) $this->input('campaign_results', []))
                ->sum(fn ($row) => (int) ($row['spend'] ?? 0));

            if ($spent > $budget) {
                $validator->errors()->add(
                    'campaign_results',
                    'Total spend (₹' . number_format($spent) . ') exceeds the available budget (₹' . number_format($budget) . ').'
                );
            }
        });
    }
}