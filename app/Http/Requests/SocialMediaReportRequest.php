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
        $assignmentIds = $this->input('assign_service_ids');

        if (is_array($assignmentIds)) {
            $this->merge([
                'assign_service_ids' => array_map(
                    fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false ? (int) $id : $id,
                    $assignmentIds
                ),
            ]);
        }
    }

    public function attributes(): array
    {
        return [
            'assign_service_ids'   => 'assign service',
            'assign_service_ids.*' => 'assign service',
            'total_conversations'  => 'total WhatsApp conversations',
            'total_enquiries'      => 'total WhatsApp enquiries',
            'whatsapp_clicks'      => 'WhatsApp clicks',
            'total_leads'          => 'total leads',
            'form_submissions'     => 'form submissions',
            'link_clicks'          => 'link clicks',
            'total_engagement'     => 'total engagement',
            'reactions'            => 'reactions',
            'comments'             => 'comments',
            'shares'               => 'shares',
            'saves'                => 'saves',
            'profile_visits'       => 'profile visits',
            'new_followers'        => 'new followers',
            'reach'                => 'reach',
            'impressions'          => 'impressions',
            'frequency'            => 'frequency',
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
            'assign_service_ids.required' => 'Select at least one assign service.',
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
            'assign_service_ids'   => ['required', 'array', 'min:1'],
            'assign_service_ids.*' => ['integer', 'distinct', 'exists:assign_services,id'],
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
            'WhatsApp Meta Ads Monthly Report' => 'whatsapp_meta_ads',
            'Lead Generation Ads Monthly Report' => 'lead_generation_ads',
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
        $validator->after(function (Validator $validator) {
            if (! $this->isMethod('post')) {
                return;
            }

            foreach ($validator->errors()->keys() as $key) {
                if ($key === 'assign_service_ids' || str_starts_with($key, 'assign_service_ids.')) {
                    return;
                }
            }

            $ids = collect((array) $this->input('assign_service_ids', []))
                ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($ids === []) {
                return;
            }

            $today = Carbon::today();
            $eligibleIds = AssignService::coveringDate($today)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (count($eligibleIds) !== count($ids)) {
                $validator->errors()->add(
                    'assign_service_ids',
                    'Today must fall within the date range of every selected assign service to submit this report.'
                );

                return;
            }

            $alreadyReported = SocialMediaReport::query()
                ->whereDate('created_at', $today)
                ->where(function ($query) use ($ids) {
                    foreach ($ids as $id) {
                        $query->orWhere(fn ($reportQuery) => $reportQuery->forAssignService($id));
                    }
                })
                ->exists();

            if ($alreadyReported) {
                $validator->errors()->add(
                    'assign_service_ids',
                    'A report entry already exists today for one or more selected assign services.'
                );
            }
        });
    }
}