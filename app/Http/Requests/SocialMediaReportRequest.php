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

    /**
     * Normalize the incoming payload BEFORE validation:
     *  - cast assign_service_ids[] values to int
     *  - rebuild nested groups (key_observations, monthly_summary, etc.)
     *    from flat dot-notation keys, in case the browser serialized
     *    `group.subkey` instead of `group[subkey]`.
     */
    protected function prepareForValidation(): void
    {
        // -----------------------------------------------------------------
        // assign_service_ids → int[]
        // -----------------------------------------------------------------
        $assignmentIds = $this->input('assign_service_ids');
        if (is_array($assignmentIds)) {
            $this->merge([
                'assign_service_ids' => array_map(
                    fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false ? (int) $id : $id,
                    $assignmentIds
                ),
            ]);
        }

        // -----------------------------------------------------------------
        // Rebuild nested groups from either flat `group.subkey` keys
        // or already-nested `group[subkey]` inputs.
        // -----------------------------------------------------------------
        $nestedGroups = [
            'key_observations',
            'performance_highlights',
            'monthly_summary',
            'next_month_plan',
            'creative_performance',
            'audience_performance',
            'content_analysis',
        ];

        $merged = [];

        foreach ($nestedGroups as $group) {
            $existing = $this->input($group);

            if (is_array($existing)) {
                // Already nested → keep as is.
                continue;
            }

            $sub = [];

            foreach ($this->all() as $key => $value) {
                if (str_starts_with($key, $group . '.')) {
                    $subKey = substr($key, strlen($group) + 1);
                    $sub[$subKey] = $value;
                }
            }

            if (! empty($sub)) {
                $merged[$group] = $sub;
            }
        }

        if (! empty($merged)) {
            $this->merge($merged);
        }
    }

    /* --------------------------------------------------------------
     |  Friendly labels — prevents "key observations.x" messages
     -------------------------------------------------------------- */
    public function attributes(): array
    {
        return [
            'assign_service_ids'   => 'assign service',
            'assign_service_ids.*' => 'assign service',

            'key_observations.creative_attention'    => 'creative attention',
            'key_observations.audience_response'     => 'audience response',
            'key_observations.placement_interaction' => 'placement interaction',

            'performance_highlights.notes' => 'performance highlights',

            'monthly_summary.overall'          => 'overall performance',
            'monthly_summary.enquiry'          => 'enquiry / lead generation',
            'monthly_summary.engagement'       => 'audience engagement',
            'monthly_summary.brand_visibility' => 'brand visibility',

            'next_month_plan.test_creatives'     => 'test new creatives',
            'next_month_plan.audience_targeting' => 'improve audience targeting',
            'next_month_plan.retargeting'        => 'retarget interested users',
            'next_month_plan.messaging'          => 'test different messaging',
            'next_month_plan.high_performers'    => 'focus on high-performers',
            'next_month_plan.quality_leads'      => 'increase quality leads',

            'total_conversations' => 'total WhatsApp conversations',
            'total_enquiries'     => 'total WhatsApp enquiries',
            'whatsapp_clicks'     => 'WhatsApp clicks',
            'reach'               => 'reach',
            'impressions'         => 'impressions',
            'frequency'           => 'frequency',

            'total_leads'      => 'total leads',
            'form_submissions' => 'form submissions',
            'link_clicks'      => 'link clicks',

            'total_engagement' => 'total engagement',
            'reactions'        => 'reactions',
            'comments'         => 'comments',
            'shares'           => 'shares',
            'saves'            => 'saves',
            'profile_visits'   => 'profile visits',
            'new_followers'    => 'new followers',

            'campaign_results'          => 'campaign results',
            'campaign_results.*.name'   => 'campaign / ad set name',
            'campaign_results.*.result' => 'campaign result',
            'campaign_results.*.spend'  => 'campaign spend',
            'campaign_results.*.note'   => 'campaign note',

            'top_posts'               => 'top posts',
            'top_posts.*.title'       => 'post title',
            'top_posts.*.engagements' => 'post engagements',
            'top_posts.*.type'        => 'post type',
        ];
    }

    /* --------------------------------------------------------------
     |  Custom messages — friendly wording for nested fields
     -------------------------------------------------------------- */
    public function messages(): array
    {
        return [
            'assign_service_ids.required' => 'Select at least one assign service.',

            'key_observations.creative_attention.required'    => 'Please describe which creative attracted more attention.',
            'key_observations.audience_response.required'     => 'Please describe which audience responded better.',
            'key_observations.placement_interaction.required' => 'Please describe which placements generated stronger interaction.',
            'performance_highlights.notes.required'           => 'Please provide the performance highlights.',

            'monthly_summary.overall.required'          => 'Provide an overall performance summary.',
            'monthly_summary.enquiry.required'          => 'Provide the enquiry / lead generation summary.',
            'monthly_summary.engagement.required'       => 'Provide the audience engagement summary.',
            'monthly_summary.brand_visibility.required' => 'Provide the brand visibility summary.',

            'next_month_plan.test_creatives.required'     => 'Describe the new creatives to test.',
            'next_month_plan.audience_targeting.required' => 'Describe how you will improve audience targeting.',
            'next_month_plan.retargeting.required'        => 'Describe the retargeting plan for interested users.',
            'next_month_plan.messaging.required'          => 'Describe the different messaging to test.',
            'next_month_plan.high_performers.required'    => 'Describe the plan to focus on high-performing audiences.',
            'next_month_plan.quality_leads.required'      => 'Describe how to increase quality leads / enquiries.',

            'campaign_results.*.name.required'   => 'Row :position — Campaign / Ad Set Name is required.',
            'campaign_results.*.result.required' => 'Row :position — Result is required.',
            'campaign_results.*.spend.required'  => 'Row :position — Spend is required.',
            'campaign_results.*.note.required'   => 'Row :position — Note is required.',

            'top_posts.*.title.required'       => 'Row :position — Post title is required.',
            'top_posts.*.engagements.required' => 'Row :position — Engagements is required.',
            'top_posts.*.type.required'        => 'Row :position — Type is required.',
        ];
    }

    /* --------------------------------------------------------------
     |  Rules — always-on + type-conditional
     -------------------------------------------------------------- */
    public function rules(): array
    {
        $rules = [
            'assign_service_ids'   => ['required', 'array', 'min:1'],
            'assign_service_ids.*' => ['integer', 'distinct', 'exists:assign_services,id'],

            // Nested — always required
            'key_observations.creative_attention'    => ['required', 'string'],
            'key_observations.audience_response'     => ['required', 'string'],
            'key_observations.placement_interaction' => ['required', 'string'],
            'performance_highlights.notes'           => ['required', 'string'],

            'monthly_summary.overall'          => ['required', 'string'],
            'monthly_summary.enquiry'          => ['required', 'string'],
            'monthly_summary.engagement'       => ['required', 'string'],
            'monthly_summary.brand_visibility' => ['required', 'string'],

            'next_month_plan.test_creatives'     => ['required', 'string'],
            'next_month_plan.audience_targeting' => ['required', 'string'],
            'next_month_plan.retargeting'        => ['required', 'string'],
            'next_month_plan.messaging'          => ['required', 'string'],
            'next_month_plan.high_performers'    => ['required', 'string'],
            'next_month_plan.quality_leads'      => ['required', 'string'],
        ];

        $types = $this->selectedReportTypes();

        if (in_array('whatsapp_meta_ads', $types, true)) {
            $rules += [
                'total_conversations' => ['required', 'integer', 'min:0'],
                'total_enquiries'     => ['required', 'integer', 'min:0'],
                'whatsapp_clicks'     => ['required', 'integer', 'min:0'],
                'reach'               => ['required', 'integer', 'min:0'],
                'impressions'         => ['required', 'integer', 'min:0'],
                'frequency'           => ['required', 'numeric', 'min:0'],
            ];
        }

        if (in_array('lead_generation_ads', $types, true)) {
            $rules += [
                'total_leads'      => ['required', 'integer', 'min:0'],
                'form_submissions' => ['required', 'integer', 'min:0'],
                'link_clicks'      => ['required', 'integer', 'min:0'],
            ];
        }

        if (array_intersect(['whatsapp_meta_ads', 'lead_generation_ads'], $types) !== []) {
            $rules += [
                'campaign_results'          => ['required', 'array', 'min:1'],
                'campaign_results.*.name'   => ['required', 'string'],
                'campaign_results.*.result' => ['required', 'string'],
                'campaign_results.*.spend'  => ['required', 'string'],
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

    /* --------------------------------------------------------------
     |  Resolve which report types the selected assignments require
     -------------------------------------------------------------- */
    private function selectedReportTypes(): array
    {
        $assignmentIds = collect((array) $this->input('assign_service_ids', []))
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($assignmentIds->isEmpty()) {
            return [];
        }

        $serviceIds = AssignService::query()
            ->whereIn('id', $assignmentIds)
            ->get(['service_id'])
            ->pluck('service_id')
            ->flatMap(fn ($ids) => is_array($ids) ? $ids : array_filter([(int) $ids]))
            ->filter()
            ->unique()
            ->values();

        $serviceNameToType = [
            'WhatsApp Meta Ads Monthly Report'       => 'whatsapp_meta_ads',
            'Lead Generation Ads Monthly Report'     => 'lead_generation_ads',
            'Social Media Engagement Monthly Report' => 'social_media_engagement',
        ];

        return Service::query()
            ->whereIn('id', $serviceIds)
            ->pluck('name')
            ->map(fn ($name) => $serviceNameToType[$name] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /* --------------------------------------------------------------
     |  Post-validation guards (date range + duplicate-today)
     -------------------------------------------------------------- */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->isMethod('post')) {
                return;
            }

            foreach ($v->errors()->keys() as $key) {
                if ($key === 'assign_service_ids' || str_starts_with($key, 'assign_service_ids.')) {
                    return;
                }
            }

            $ids = array_values(array_unique(array_map(
                'intval',
                (array) $this->input('assign_service_ids', [])
            )));

            if (empty($ids)) {
                return;
            }

            $today = Carbon::today();

            $eligibleIds = AssignService::coveringDate($today)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (count($eligibleIds) !== count($ids)) {
                $v->errors()->add(
                    'assign_service_ids',
                    'Today must fall within the date range of every selected assign service to submit this report.'
                );
                return;
            }

            $alreadyReported = SocialMediaReport::query()
                ->whereDate('created_at', $today)
                ->where(function ($query) use ($ids) {
                    foreach ($ids as $id) {
                        $query->orWhere(fn ($q) => $q->forAssignService($id));
                    }
                })
                ->exists();

            if ($alreadyReported) {
                $v->errors()->add(
                    'assign_service_ids',
                    'A report entry already exists today for one or more selected assign services.'
                );
            }
        });
    }
}