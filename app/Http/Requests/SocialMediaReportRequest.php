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

    public function rules(): array
    {
        $rules = [
            'assign_service_ids'   => ['required', 'array', 'min:1'],
            'assign_service_ids.*' => ['integer', 'distinct', 'exists:assign_services,id'],

            'platforms'          => ['required', 'string', 'max:255'],
            'campaign_objective' => ['required', 'string', 'max:255'],
            'campaign_overview'  => ['required', 'string'],
            'target_audience'    => ['required', 'string'],
            'campaign_focus'     => ['required', 'string'],
            'key_highlights'     => ['required', 'string'],

            'key_observations.creative_attention' => ['required', 'string'],
            'key_observations.audience_response' => ['required', 'string'],
            'key_observations.placement_interaction' => ['required', 'string'],
            'performance_highlights.notes' => ['required', 'string'],
            'monthly_summary.overall' => ['required', 'string'],
            'monthly_summary.enquiry' => ['required', 'string'],
            'monthly_summary.engagement' => ['required', 'string'],
            'monthly_summary.brand_visibility' => ['required', 'string'],
            'next_month_plan.test_creatives' => ['required', 'string'],
            'next_month_plan.audience_targeting' => ['required', 'string'],
            'next_month_plan.retargeting' => ['required', 'string'],
            'next_month_plan.messaging' => ['required', 'string'],
            'next_month_plan.high_performers' => ['required', 'string'],
            'next_month_plan.quality_leads' => ['required', 'string'],
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
                'campaign_results'         => ['required', 'array', 'min:1'],
                'campaign_results.*.name'  => ['required', 'string'],
                'campaign_results.*.result' => ['required', 'string'],
                'campaign_results.*.spend' => ['required', 'string'],
                'campaign_results.*.note'  => ['required', 'string'],
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
                'top_posts'        => ['required', 'array', 'min:1'],
                'top_posts.*.title'=> ['required', 'string'],
                'top_posts.*.engagements' => ['required', 'string'],
                'top_posts.*.type'  => ['required', 'string'],
            ];
        }

        return $rules;
    }

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
                        $query->orWhereJsonContains('assign_service_ids', $id);
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
