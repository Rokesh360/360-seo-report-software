<?php

namespace App\Http\Requests;

use App\Models\AssignService;
use App\Models\GoogleAdsReport;
use App\Support\ReportServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GoogleAdsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'assign_service_id' => ['required', 'integer', 'exists:assign_services,id'],
            'data' => ['required', 'array'],
            'data.clicks' => ['required', 'integer', 'min:0'],
            'data.impressions' => ['required', 'integer', 'min:0'],
            'data.total_cost' => ['required', 'numeric', 'min:0'],
            'data.demographics' => ['required', 'array', 'min:1'],
            'data.demographics.*.gender' => ['required', 'string', 'max:60'],
            'data.demographics.*.clicks' => ['required', 'integer', 'min:0'],
            'data.locations' => ['required', 'array', 'min:1'],
            'data.locations.*.campaign' => ['required', 'string', 'max:255'],
            'data.locations.*.cpc' => ['required', 'numeric', 'min:0'],
            'data.locations.*.impressions' => ['required', 'integer', 'min:0'],
            'data.key_metrics' => ['required', 'array', 'min:1'],
            'data.key_metrics.*.metric' => ['required', 'string', 'max:255'],
            'data.key_metrics.*.value' => ['required', 'string', 'max:255'],
            'data.key_metrics.*.insight' => ['required', 'string', 'max:1000'],
            'overview_images' => ['nullable', 'array', 'max:12'],
            'overview_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string', 'max:255'],
        ];

        $assignment = AssignService::find((int) $this->input('assign_service_id'));
        $googleAdsTypes = collect($assignment?->getServices() ?? [])
            ->map(fn($service) => ReportServiceType::googleAdsTypeFromServiceName($service->name))
            ->filter()
            ->values();

        if ($googleAdsTypes->contains('google_youtube_ads')) {
            $rules['data.views'] = ['required', 'integer', 'min:0'];
            $rules['data.interactions'] = ['required', 'integer', 'min:0'];
        } else {
            $rules['data.locations.*.avg_cpc'] = ['required', 'numeric', 'min:0'];
        }

        if ($googleAdsTypes->contains('google_call_ads')) {
            $rules['data.calls'] = ['required', 'integer', 'min:0'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $assignment = AssignService::find((int) $this->input('assign_service_id'));
            $report = $this->route('googleAdsReport');
            if ($report instanceof GoogleAdsReport && $assignment?->id !== $report->assign_service_id) {
                $validator->errors()->add('assign_service_id', 'The assignment cannot be changed for this report.');

                return;
            }

            $isGoogleAds = $assignment && $assignment->getServices()
                ->contains(fn($service) => ReportServiceType::googleAdsTypeFromServiceName($service->name) !== null);

            if (! $isGoogleAds) {
                $validator->errors()->add('assign_service_id', 'Select an assignment with a Google Ads service.');

                return;
            }

            $existingImages = $report instanceof GoogleAdsReport ? $report->overview_images ?? [] : [];
            $removedImages = array_intersect($existingImages, (array) $this->input('remove_images', []));
            $newImages = $this->file('overview_images', []);

            if (array_diff($existingImages, $removedImages) === [] && $newImages === []) {
                $validator->errors()->add('overview_images', 'Upload at least one campaign overview screenshot.');
            }

            $newCount = is_array($newImages) ? count($newImages) : 1;
            if (count(array_diff($existingImages, $removedImages)) + $newCount > 12) {
                $validator->errors()->add('overview_images', 'You can have a maximum of 12 images in total.');
            }

            $data = (array) $this->input('data', []);
            $totalClicks = (int) ($data['clicks'] ?? 0);
            $audienceClicks = collect($data['demographics'] ?? [])
                ->sum(fn($row) => (int) ($row['clicks'] ?? 0));

            if ($audienceClicks > $totalClicks) {
                $validator->errors()->add('data.demographics', 'Audience clicks cannot exceed total campaign clicks.');
            }

            $budget = (float) ($assignment->budget ?? 0);
            $cost = (float) ($data['total_cost'] ?? 0);
            if ($budget > 0 && $cost > $budget) {
                $validator->errors()->add('data.total_cost', 'Total cost cannot exceed the assigned budget.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'overview_images.*.uploaded' => 'An image failed to upload. Each image must be 10 MB or less, and the total upload must be within the server limit.',
            'overview_images.*.image' => 'Each file must be an image.',
            'overview_images.*.mimes' => 'Images must be JPG, PNG or WebP.',
            'overview_images.*.max' => 'Each image must be 10 MB or less.',
        ];
    }
}
